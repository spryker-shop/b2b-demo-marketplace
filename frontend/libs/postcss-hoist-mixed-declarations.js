/**
 * PostCSS plugin: restore Sass' pre-1.92 "mixed declarations" hoisting at build time.
 *
 * Background:
 * Dart Sass >= 1.92 no longer hoists declarations that appear after nested rules — it keeps
 * them in source order (the "mixed-declarations" change). In Spryker's Yves theming, base
 * component declarations that are authored/injected (via mixins or @content) after a base
 * modifier now end up AFTER `.x--modifier` in the compiled CSS. Because both carry equal
 * specificity, the later base rule wins and the modifier silently stops working.
 *
 * This plugin reorders the COMPILED CSS to emit a base class rule before the modifier rules
 * derived from it — exactly what Sass used to do — without touching any source or the Sass
 * version.
 *
 * Safety contract: a plain single-class rule `.x` is only reordered past a CONTIGUOUS run of
 * rules whose selectors are all derived from `.x` (`.x`, `.x--m`, `.x__e`, `.x:hover`,
 * `.x[..]`, `.x .child`, ...). The moment an unrelated selector or an at-rule is hit, the run
 * stops. Nothing is ever moved across an unrelated selector, so no other cascade is affected.
 * Two outcomes are possible:
 *   - a matching base rule already exists earlier in the run  -> merge declarations into it;
 *   - only modifiers precede the base rule                    -> move the base rule above them.
 */

const BOUNDARY = new Set(['-', '_', ':', '.', '[', ' ', '>', '+', '~', '(']);

const isPlainClass = (selector) => /^\.[A-Za-z0-9_-]+$/.test(selector);

const isDerivedFrom = (selector, base) =>
    selector.split(',').every((rawPart) => {
        const part = rawPart.trim();

        if (part === base) {
            return true;
        }

        if (!part.startsWith(base)) {
            return false;
        }

        return BOUNDARY.has(part[base.length]);
    });

const hoistRule = (node) => {
    const base = node.selector.trim();
    let earliestBaseRule = null;
    let runStart = null;
    let sawDerived = false;

    let prev = node.prev();

    while (prev && prev.type === 'rule') {
        const prevSelector = prev.selector.trim();

        if (prevSelector === base) {
            earliestBaseRule = prev;
            runStart = prev;
        } else if (isDerivedFrom(prevSelector, base)) {
            sawDerived = true;
            runStart = prev;
        } else {
            break;
        }

        prev = prev.prev();
    }

    if (earliestBaseRule) {
        node.each((child) => earliestBaseRule.append(child.clone()));
        node.remove();

        return;
    }

    if (sawDerived && runStart) {
        node.remove();
        runStart.before(node);
    }
};

const processContainer = (container) => {
    if (!container.nodes) {
        return;
    }

    let node = container.first;

    while (node) {
        const next = node.next();

        if (node.type === 'rule' && isPlainClass(node.selector.trim())) {
            hoistRule(node);
        }

        node = next;
    }
};

const plugin = () => ({
    postcssPlugin: 'postcss-hoist-mixed-declarations',
    OnceExit(root) {
        processContainer(root);

        root.walkAtRules((atRule) => {
            if (atRule.nodes) {
                processContainer(atRule);
            }
        });
    },
});

plugin.postcss = true;

module.exports = plugin;
