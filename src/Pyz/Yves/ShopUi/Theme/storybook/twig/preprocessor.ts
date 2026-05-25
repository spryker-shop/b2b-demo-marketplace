/* eslint-disable max-lines */
interface MacroParam {
    name: string;
    def: string | null;
}

interface MacroDef {
    params: MacroParam[];
    body: string;
}

type MacroMap = Record<string, MacroDef>;

function preprocessTernaryNoElse(source: string): string {
    // twig.js parser bug: inside an object literal, `key: A ? B` (ternary with no
    // else clause) silently evaluates to empty — the value is lost. Spryker's
    // vendor define blocks rely heavily on this shorthand, e.g.
    //   image: data.product.images is defined ? data.product.images.0.externalUrlSmall | default,
    // PHP Twig accepts it as `A ? B : ''`, so we add the explicit empty else.
    // Walk char-by-char, track quote and bracket depth, and at every `?` check
    // whether a matching `:` is reached before the ternary ends.
    let out = '';
    let i = 0;
    while (i < source.length) {
        const ch = source[i];
        // Skip `??` (null coalescing) — not a ternary.
        if (ch === '?' && source[i + 1] === '?') {
            out += '??';
            i += 2;
            continue;
        }
        if (ch !== '?') {
            out += ch;
            i++;
            continue;
        }
        // Found `?`. Find the boundary.
        let j = i + 1;
        let paren = 0;
        let bracket = 0;
        let brace = 0;
        let quote: string | null = null;
        let foundColon = false;
        let boundary = -1;
        while (j < source.length) {
            const c = source[j];
            if (quote) {
                if (c === quote && source[j - 1] !== '\\') quote = null;
                j++;
                continue;
            }
            if (c === "'" || c === '"') {
                quote = c;
                j++;
                continue;
            }
            if (c === '(') paren++;
            else if (c === ')') {
                if (paren === 0) {
                    boundary = j;
                    break;
                }
                paren--;
            } else if (c === '[') bracket++;
            else if (c === ']') {
                if (bracket === 0) {
                    boundary = j;
                    break;
                }
                bracket--;
            } else if (c === '{') brace++;
            else if (c === '}') {
                if (brace === 0) {
                    boundary = j;
                    break;
                }
                brace--;
            } else if (paren === 0 && bracket === 0 && brace === 0) {
                if (c === ',') {
                    boundary = j;
                    break;
                }
                if (c === ':') {
                    foundColon = true;
                    break;
                }
                // Twig tag close
                if (c === '%' && source[j + 1] === '}') {
                    boundary = j;
                    break;
                }
                if (c === '}' && source[j + 1] === '}') {
                    boundary = j;
                    break;
                }
            }
            j++;
        }
        if (!foundColon && boundary > 0) {
            out += source.slice(i, boundary) + " : ''";
            i = boundary;
            continue;
        }
        out += ch;
        i++;
    }
    return out;
}

function preprocessDefine(source: string): string {
    const defineRegex = /\{%-?\s*define\s+(\w+)\s*=\s*([\s\S]*?)\s*-?%\}/g;

    return source.replace(defineRegex, (_match, varName, expression) => {
        const trimmed = expression.trim();
        const isArray = trimmed.startsWith('[');
        const defaultVal = isArray ? '[]' : '{}';

        return (
            `{% if ${varName} is not defined %}{% set ${varName} = ${defaultVal} %}{% endif %}` +
            `{% set ${varName} = (${expression}) | merge(${varName}) %}`
        );
    });
}

declare const __WIDGET_MAP__: Record<string, string>;

const widgetMap: Record<string, string> = typeof __WIDGET_MAP__ !== 'undefined' ? __WIDGET_MAP__ : {};

function extractWidgetWithClause(tagContent: string): string | null {
    // Pull the `with { … }` payload out of a `{% widget 'Foo' args [...] [use view(...)] with {…} only %}` tag.
    // Tracks bracket depth + quotes so nested objects aren't truncated.
    const idx = tagContent.search(/\bwith\b/);
    if (idx === -1) return null;
    let i = idx + 4;
    while (i < tagContent.length && /\s/.test(tagContent[i])) i++;
    if (tagContent[i] !== '{') return null;
    let depth = 0;
    let quote: string | null = null;
    const start = i;
    while (i < tagContent.length) {
        const c = tagContent[i];
        if (quote) {
            if (c === quote && tagContent[i - 1] !== '\\') quote = null;
        } else if (c === "'" || c === '"') {
            quote = c;
        } else if (c === '{') depth++;
        else if (c === '}') {
            depth--;
            if (depth === 0) return tagContent.slice(start, i + 1);
        }
        i++;
    }
    return null;
}

function rewriteWidgets(source: string): string {
    // Replace `{% widget 'Foo' … %}…{% endwidget %}` with `{% include 'real/path.twig' [with {…}] ignore missing %}`.
    // The path comes from the PHP class' `getTemplate()`, captured at storybook
    // build time into `__WIDGET_MAP__`. Positional `args [...]` cannot be
    // forwarded (they map to a PHP constructor that calls `addParameter()` —
    // not reproducible in twig.js), but any `with {…}` clause is preserved so
    // stories can still pass context through. Unknown widgets are stripped.
    let result = source;
    let safety = 200;

    while (/\{%-?\s*widget\s/.test(result) && safety-- > 0) {
        const openMatch = /\{%-?\s*widget\s+([^\s%]+)/.exec(result);
        if (!openMatch) break;
        const tagStart = openMatch.index;
        const firstArg = openMatch[1];
        // Quoted: real widget name we can resolve via __WIDGET_MAP__.
        // Unquoted: a variable whose value comes from `findWidget()` at runtime —
        // can't be resolved at preprocess time, so the block gets stripped.
        const quotedMatch = firstArg.match(/^'([^']+)'$/);
        const widgetName = quotedMatch ? quotedMatch[1] : null;

        const tagBodyEnd = result.indexOf('%}', tagStart);
        if (tagBodyEnd === -1) break;
        const tagEnd = tagBodyEnd + 2;
        const openTag = result.slice(tagStart, tagEnd);

        // Walk to matching `{% endwidget %}`. Spryker also supports
        // `{% elsewidget … %}` as an in-block alternative — treat it like a
        // sibling open tag (we drop everything inside regardless of branch).
        let depth = 1;
        const cursor = tagEnd;
        let endIdx = -1;
        const tagRe = /\{%-?\s*(widget|elsewidget|endwidget)\b/g;
        tagRe.lastIndex = cursor;
        for (let m = tagRe.exec(result); m !== null; m = tagRe.exec(result)) {
            if (m[1] === 'widget') {
                depth++;
                continue;
            }
            if (m[1] === 'elsewidget') continue;
            depth--;
            if (depth === 0) {
                const close = result.indexOf('%}', m.index);
                if (close !== -1) endIdx = close + 2;
                break;
            }
        }
        if (endIdx === -1) break;

        // `use view('view-name', 'ModuleName')` overrides the widget's default
        // template — Spryker uses this to render a widget through a sibling
        // module's view (e.g. ProductGroupColorWidget through
        // ProductGroupWidget's `product-item-color-selector` view). Resolve to
        // `@ModuleName/views/<view>/<view>.twig` and prefer it over widgetMap.
        const useViewMatch = /\buse\s+view\s*\(\s*'([^']+)'\s*,\s*'([^']+)'\s*\)/.exec(openTag);
        const viewPath = useViewMatch
            ? `@${useViewMatch[2]}/views/${useViewMatch[1]}/${useViewMatch[1]}.twig`
            : undefined;
        const templatePath = viewPath || (widgetName ? widgetMap[widgetName] : undefined);
        let replacement = '';
        if (templatePath) {
            const withClause = extractWidgetWithClause(openTag);
            // Spryker widget twigs read `_widget.<...>` (the PHP widget instance);
            // surface it from the story context's optional `widgets` map. Stories
            // that want a non-empty render (e.g. populated rating) pass
            // `widgets: { DisplayProductAbstractReviewWidget: { … } }`.
            const widgetCtx = `_widget: (widgets is defined and widgets['${widgetName}'] is defined) ? widgets['${widgetName}'] : null`;
            let ctxBody: string;
            if (withClause) {
                // Trim back to the closing `}` and drop any trailing comma so
                // we can splice in the synthetic `_widget` entry cleanly.
                const inner = withClause.slice(1, -1).replace(/,\s*$/, '').trim();
                ctxBody = inner ? `{ ${inner}, ${widgetCtx} }` : `{ ${widgetCtx} }`;
            } else {
                ctxBody = `{ ${widgetCtx} }`;
            }
            // twig.js include regex: `path [ignore missing] [with X] [only]` — keep that order.
            replacement = `{% include "${templatePath}" ignore missing with ${ctxBody} %}`;
        }

        result = result.slice(0, tagStart) + replacement + result.slice(endIdx);
    }

    return result;
}

function preprocessWidgets(source: string): string {
    let result = rewriteWidgets(source);
    result = result.replace(/\{%-?\s*cms_slot\s+[\s\S]*?-?%\}/g, '');
    // Twig's `include(paths, ctx, with_context = false)` function form trips up
    // twig.js's parser at the named-arg `=` sign. Drop the trailing
    // `, with_context = …` argument so the include falls back to its default
    // (with_context = true), which still renders the template body.
    result = result.replace(/,\s*with_context\s*=\s*(?:true|false)/g, '');
    return result;
}

function preprocessArrowFunctions(source: string): string {
    let result = source.replace(/\|\s*map\(\s*\w+\s*=>\s*\w+\s*\|\s*first\s*\)/g, '| sb_map_first');
    result = result.replace(/\|\s*filter\(\s*\([^)]*\)\s*=>[^)]*\)/g, '');
    result = result.replace(/\|\s*reduce\(\s*\([^)]*\)\s*=>[^)]*\)/g, '');
    result = result.replace(/(\w+(?:\[\w+\])?)\s*\?\?\s*(true|false|\d+|'[^']*')/g, '$1 | default($2)');
    return result;
}

function preprocessOnly(source: string): string {
    // twig.js supports `{% include "x" with {} only %}` but NOT `{% include "x" only %}`.
    // Add `with {}` before bare `only` keywords so twig.js can parse them.
    return source.replace(/(%\}|'|"|\))\s+only\s*(-?%\})/g, (match, before: string, after: string) => {
        if (before === '%}') return match;
        return before + ' with {} only ' + after.replace(/^-?/, '');
    });
}

function preprocessParentCalls(source: string): string {
    // No-op now that `hoistNestedBlocks()` (engine.ts) pre-registers every
    // nested block via the parsed token tree — twig.js's `parent()` resolves
    // through the registered blocks regardless of nesting depth, so we no
    // longer need to strip deep `parent()` calls. Pyz product-item-list, for
    // example, calls `parent()` at depth 4 (body → imageContainer → image →
    // productThumbnail) which used to be stripped and produced an empty link.
    return source;
}

// Synthetic copies of the macros from `models/component.twig`, injected into
// templates that rely on macro resolution through `{% extends %}` — which
// twig.js cannot follow. Bodies match the vendor model byte-for-byte.
const COMPONENT_MACRO_DEFS =
    '{% macro renderClass(name, modifiers, extra) %}' +
    '{{-name | trim-}}' +
    '{%- for modifier in modifiers | default([]) -%}' +
    '{%- if modifier | trim is not empty %} {{name}}--{{modifier | trim}}{% endif -%}' +
    '{% endfor -%}' +
    '{%- if extra %} {{extra-}}{% endif -%}' +
    '{% endmacro %}\n' +
    '{% macro renderAttributes(attributes) %}' +
    '{%- for name, value in attributes | default({}) -%}' +
    '{%- if value is same as(true) -%}' +
    "{{-' ' ~ name-}}" +
    '{%- elseif value is not same as(false) -%}' +
    "{{-' ' ~ name-}}='{{-value-}}'" +
    '{%- endif -%}' +
    '{%- endfor -%}' +
    '{% endmacro %}\n';

function preprocessMacros(source: string): string {
    // twig.js does not support `{% macro %}` with `{% embed %}` inside, and it
    // cannot resolve macros inherited through `{% extends %}`. We:
    //   1. Inline only macros whose body contains `{% embed %}` at their call
    //      sites (e.g. product-item.thumbnail). Plain macros are left intact so
    //      other templates that `{% import %}` them keep working.
    //   2. For templates that import `_self as X` but define no macros locally,
    //      synthesize `renderClass`/`renderAttributes` macros from the component
    //      model so calls resolve in both expression and output contexts.
    const hasLocalMacros = /\{%-?\s*macro\s+/.test(source);

    if (!hasLocalMacros) {
        if (!/\{%-?\s*import\s+_self\s+as\s+\w+/.test(source)) return source;
        const extendsMatch = source.match(/^([\s\S]*?\{%-?\s*extends\s+[^%]*-?%\})([\s\S]*)$/);
        if (extendsMatch) {
            return extendsMatch[1] + '\n' + COMPONENT_MACRO_DEFS + extendsMatch[2];
        }
        return COMPONENT_MACRO_DEFS + source;
    }

    const macros: MacroMap = {};
    const macroRe = /\{%-?\s*macro\s+(\w+)\s*\(([^)]*)\)\s*-?%\}([\s\S]*?)\{%-?\s*endmacro\s*-?%\}/g;
    let stripped = source.replace(macroRe, (full, name: string, paramList: string, body: string) => {
        // Only strip+inline macros that wrap an {% embed %} — those don't render
        // correctly through twig.js's normal macro path. Leave plain macros so
        // other templates that import them keep working.
        if (!/\{%-?\s*embed\s+/.test(body)) return full;

        const params: MacroParam[] = paramList
            .split(',')
            .map((p) => p.trim())
            .filter(Boolean)
            .map((p) => {
                const eq = p.indexOf('=');
                return eq === -1
                    ? { name: p, def: null }
                    : { name: p.slice(0, eq).trim(), def: p.slice(eq + 1).trim() };
            });
        macros[name] = { params, body };
        return '';
    });

    if (!Object.keys(macros).length) return source;

    const aliases = new Set<string>();
    stripped = stripped.replace(/\{%-?\s*import\s+_self\s+as\s+(\w+)\s*-?%\}/g, (_, alias: string) => {
        aliases.add(alias);
        return '';
    });

    if (!aliases.size) aliases.add('macros');

    return inlineMacroCalls(stripped, macros, aliases);
}

function inlineMacroCalls(source: string, macros: MacroMap, aliases: Set<string>): string {
    const escapeRe = (s: string) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    let out = source;
    for (const alias of aliases) {
        for (const [name, { params, body }] of Object.entries(macros)) {
            const callRe = new RegExp(
                `\\{\\{\\s*-?\\s*${escapeRe(alias)}\\.${escapeRe(name)}\\s*\\(([\\s\\S]*?)\\)\\s*-?\\s*\\}\\}`,
                'g',
            );
            out = out.replace(callRe, (_, argsStr: string) => {
                const args = splitTwigArgList(argsStr);
                const setStmts = params
                    .map((param, idx) => {
                        const value = args[idx] !== undefined ? args[idx] : param.def !== null ? param.def : "''";
                        return `{% set ${param.name} = ${value} %}`;
                    })
                    .join('');
                return setStmts + body;
            });
        }
    }
    return out;
}

/**
 * Split a Twig macro argument list (the contents between `(` and `)`) into
 * top-level argument expressions. A naive `.split(',')` would mis-split
 * structured args like `{ a: 1, b: 2 }` or `[x, y]`, so this scanner tracks
 * bracket nesting and quoted strings.
 */
function splitTwigArgList(argList: string): string[] {
    const args: string[] = [];
    let bracketDepth = 0;
    let buffer = '';
    let openQuote: string | null = null;

    for (let i = 0; i < argList.length; i++) {
        const ch = argList[i];

        if (openQuote) {
            if (ch === openQuote && argList[i - 1] !== '\\') openQuote = null;
            buffer += ch;
            continue;
        }
        if (ch === "'" || ch === '"') {
            openQuote = ch;
            buffer += ch;
            continue;
        }
        if (ch === '(' || ch === '[' || ch === '{') bracketDepth++;
        else if (ch === ')' || ch === ']' || ch === '}') bracketDepth--;

        if (ch === ',' && bracketDepth === 0) {
            args.push(buffer.trim());
            buffer = '';
            continue;
        }
        buffer += ch;
    }
    if (buffer.trim()) args.push(buffer.trim());
    return args;
}

function preprocessLiftConditionalBlocks(source: string): string {
    // No-op. We used to rewrite `{% if X %}{% block N %}…{% endblock %}{% endif %}`
    // to either drop the if (broke conditional auto-render: breadcrumb-step
    // showed both `<a>` and `<form>`) or move it inside the block (broke
    // `block('N')` calls: pyz product-item-list got an empty title because
    // vendor's `block title` is wrapped in `{% if data.name and not data.url %}`
    // and the moved if blanks the body when called explicitly with a url).
    //
    // `hoistNestedBlocks()` in engine.ts now walks the parsed token tree and
    // pre-registers every nested block — including ones inside `{% if %}` —
    // so `block('N')` resolves regardless of conditional state. Auto-render
    // keeps respecting the if. Both cases work without source rewriting.
    return source;
}

function preprocessStripBranchBlocks(source: string): string {
    // twig.js's parser breaks on `{% if %}{% block N %}…{% endblock %}{% else %}…{% endif %}`
    // — it sees `{% else %}` while still inside the (closed) block scope and
    // throws "Twig.logic.type.else not expected after a Twig.logic.type.block".
    // Real Twig accepts this (block declarations are hoisted regardless of the
    // surrounding conditional). Strip the inner `{% block N %}…{% endblock %}`
    // wrappers when they are the sole content of an if/elseif/else branch — the
    // block name is lost, but content still renders conditionally, which is all
    // we need for storybook previews.
    type Tok = { type: string; start: number; end: number };
    const tokens: Tok[] = [];
    const tokenRe = /\{%-?\s*(if|elseif|else|endif|block|endblock)\b[^%]*-?%\}/g;
    for (let m = tokenRe.exec(source); m !== null; m = tokenRe.exec(source)) {
        tokens.push({ type: m[1], start: m.index, end: m.index + m[0].length });
    }

    const ranges: Array<[number, number]> = [];
    for (let i = 0; i < tokens.length; i++) {
        const tok = tokens[i];
        if (tok.type !== 'block') continue;

        const prev = tokens[i - 1];
        if (!prev) continue;
        if (prev.type !== 'if' && prev.type !== 'else' && prev.type !== 'elseif') continue;
        if (source.slice(prev.end, tok.start).trim() !== '') continue;

        let depth = 1;
        let endIdx = -1;
        for (let j = i + 1; j < tokens.length; j++) {
            if (tokens[j].type === 'block') depth++;
            else if (tokens[j].type === 'endblock') {
                depth--;
                if (depth === 0) {
                    endIdx = j;
                    break;
                }
            }
        }
        if (endIdx === -1) continue;

        const next = tokens[endIdx + 1];
        if (!next) continue;
        if (next.type !== 'else' && next.type !== 'elseif' && next.type !== 'endif') continue;
        if (source.slice(tokens[endIdx].end, next.start).trim() !== '') continue;

        // `{% if %}{% block %}{% endblock %}{% endif %}` (no else branch) is the
        // domain of `preprocessLiftConditionalBlocks` — it hoists the block to
        // file scope so `block(name)` calls still resolve. Leave it alone here.
        if (prev.type === 'if' && next.type === 'endif') continue;

        ranges.push([tok.start, tok.end]);
        ranges.push([tokens[endIdx].start, tokens[endIdx].end]);
    }

    ranges.sort((a, b) => b[0] - a[0]);
    let result = source;
    for (const [s, e] of ranges) {
        result = result.slice(0, s) + result.slice(e);
    }
    return result;
}

function preprocessEmbedToInclude(source: string): string {
    // twig.js's `{% embed %}` does not propagate parent block content reliably,
    // especially after macro inlining. Where an embed only adds an inner override
    // that ends up calling `parent()`, drop the override and use `{% include %}`
    // so the embedded template's default content renders.
    //
    // The override may contain `{% set X = expr %}` that the embedded template
    // reads back (vendor's lazy-image embed sets `imageExtraClasses` so the
    // outer product-item gets a `js-product-item__image` class on its <img>).
    // `{% include %}` runs in a separate scope, so we hoist those `set`s into
    // the include's `with` clause, keeping the wiring intact.
    const embedRe = /\{%-?\s*embed\s+([\s\S]*?)\s*-?%\}([\s\S]*?)\{%-?\s*endembed\s*-?%\}/g;
    return source.replace(embedRe, (match, head: string, body: string) => {
        const stripped = body
            .replace(/\{%-?\s*block\s+\w+\s*-?%\}|\{%-?\s*endblock\s*-?%\}|\{\{\s*parent\(\)\s*\}\}/g, '')
            .trim();
        // Extract every top-level `{% set NAME = EXPR %}`; anything else means
        // the override has real content we shouldn't drop, so bail.
        const setRe = /\{%-?\s*set\s+(\w+)\s*=\s*([\s\S]*?)\s*-?%\}/g;
        const sets: Array<{ name: string; expr: string }> = [];
        let cursor = 0;
        let match2: RegExpExecArray | null;
        while ((match2 = setRe.exec(stripped)) !== null) {
            if (stripped.slice(cursor, match2.index).trim() !== '') return match;
            sets.push({ name: match2[1], expr: match2[2].trim() });
            cursor = match2.index + match2[0].length;
        }
        if (stripped.slice(cursor).trim() !== '') return match;

        if (sets.length === 0) return `{% include ${head} %}`;

        // The hoisted set runs in OUTER scope (the include site) instead of
        // inside the original block-content override. Spryker's macro builds
        // `embed: { jsName: jsName }` in the with clause, then references
        // `embed.jsName` inside its block override. Once hoisted, `embed`
        // resolves to the outer-scope `embed` variable (often undefined),
        // not the with-clause object — rewrite `embed.X` to a fallback that
        // also tries the local with-clause keys we just defined.
        const withInner = head.match(/\bwith\s+\{([\s\S]*?)\}\s*(?:only)?\s*$/)?.[1] ?? '';
        const localKeys: Record<string, string> = {};
        // Naive scan for `key: jsName` style entries — enough for the macros
        // we actually inline (lazy-image's `embed: { jsName: jsName }`).
        const embedKv = withInner.match(/embed\s*:\s*\{([^}]+)\}/);
        if (embedKv) {
            for (const part of embedKv[1].split(',')) {
                const kv = part.match(/^\s*(\w+)\s*:\s*([\s\S]+?)\s*$/);
                if (kv) localKeys[kv[1]] = kv[2];
            }
        }
        const rewriteExpr = (expr: string): string => {
            return expr.replace(/\bembed\.(\w+)\b/g, (_, prop) => {
                const fallback = localKeys[prop];
                return fallback ? `(${fallback})` : `embed.${prop}`;
            });
        };

        const inject = sets.map((s) => `${s.name}: (${rewriteExpr(s.expr)})`).join(', ');
        const headRe = /^([\s\S]*?\bwith\s+\{)([\s\S]*?)(\}\s*(?:only)?\s*)$/;
        const m = head.match(headRe);
        if (!m) return `{% include ${head} %}`;
        const inner = m[2].replace(/,\s*$/, '').trim();
        const newHead = `${m[1]} ${inner ? `${inner}, ` : ''}${inject} ${m[3]}`;
        return `{% include ${newHead} %}`;
    });
}

function preprocessLazyImagePreserveSets(source: string): string {
    // lazy-image starts `block body` with `set imageExtraClasses = ''` and
    // `set backgroundExtraClasses = ''`. Vendor's product-item embeds
    // lazy-image and its `block content` override re-sets `imageExtraClasses`
    // before `parent()` so the image picks up `js-product-item__image`.
    // We rewrite that embed to a plain include with the set hoisted into the
    // `with` clause — but lazy-image's reset to `''` then wipes it. Convert
    // the resets to `| default('')` so callers can pre-populate the value.
    if (!/components\/molecules\/lazy-image\/lazy-image\.twig/.test('')) {
        // marker: this transform is generic, but it's only meaningful for
        // lazy-image-shaped templates. We apply it to any source that defines
        // both variables — the change is a strict no-op when no caller passes
        // them in.
    }
    return source
        .replace(
            /\{%-?\s*set\s+imageExtraClasses\s*=\s*''\s*-?%\}/g,
            "{% set imageExtraClasses = imageExtraClasses | default('') %}",
        )
        .replace(
            /\{%-?\s*set\s+backgroundExtraClasses\s*=\s*''\s*-?%\}/g,
            "{% set backgroundExtraClasses = backgroundExtraClasses | default('') %}",
        );
}

export function preprocess(source: string): string {
    let result = preprocessLazyImagePreserveSets(source);
    result = preprocessTernaryNoElse(result);
    result = preprocessDefine(result);
    result = preprocessWidgets(result);
    result = preprocessArrowFunctions(result);
    result = preprocessOnly(result);
    result = preprocessStripBranchBlocks(result);
    result = preprocessLiftConditionalBlocks(result);
    result = preprocessMacros(result);
    result = preprocessEmbedToInclude(result);
    result = preprocessParentCalls(result);
    return result;
}
