/* eslint-disable no-console */
import Twig from 'twig';
import { preprocess } from './preprocessor';

interface FailedTemplate {
    id: string;
    reason: string;
}

type TwigTemplate = ReturnType<typeof Twig.twig>;
type TwigInternal = {
    Templates?: {
        registry: Record<string, TwigTemplate>;
    };
};

const REQUIRED_VALUE = '___REQUIRED___';

let activeTranslations: Record<string, string> = {};

function pascalToKebab(str: string): string {
    return str.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase();
}

export function resolveVendorModulePath(moduleRef: string): string | null {
    const match = moduleRef.match(/^@?(\w+):(\w+)$/);
    if (!match) return null;
    const [, org, moduleName] = match;
    return `vendor/${pascalToKebab(org)}/${pascalToKebab(moduleName)}/src/${org}/Yves/${moduleName}/Theme/default`;
}

let initialized = false;

export function initTwigEngine(): void {
    if (initialized) return;
    Twig.cache(false);

    const componentPath = (type: string, name: string, mod?: string): string => {
        const m = mod || 'ShopUi';
        return `@${m}/components/${type}/${name}/${name}.twig`;
    };

    Twig.extendFunction('model', (name: string) => `@ShopUi/models/${name}.twig`);
    Twig.extendFunction('atom', (name: string, mod?: string) => componentPath('atoms', name, mod));
    Twig.extendFunction('molecule', (name: string, mod?: string) => componentPath('molecules', name, mod));
    Twig.extendFunction('organism', (name: string, mod?: string) => componentPath('organisms', name, mod));
    Twig.extendFunction('template', (name: string, mod?: string) => {
        const m = mod || 'ShopUi';
        return `@${m}/templates/${name}/${name}.twig`;
    });
    Twig.extendFunction('view', (name: string, mod?: string) => {
        const m = mod || 'ShopUi';
        return `@${m}/views/${name}/${name}.twig`;
    });

    Twig.extendFunction('qa', (...args: unknown[]) => {
        const values = (args.flat() as unknown[]).filter(Boolean);
        return values.length ? `data-qa="${values.join(' ').trim()}"` : '';
    });

    Twig.extendFunction('csrf_token', () => '');
    Twig.extendFunction('publicPath', (rel?: string) => `/${rel || ''}`);
    Twig.extendFunction('path', (name?: string) => (name ? `/storybook-mock/${name}` : '#'));
    Twig.extendFunction('url', (name?: string) => (name ? `/storybook-mock/${name}` : '#'));
    Twig.extendFunction('is_granted', () => true);
    Twig.extendFunction('can', () => true);
    Twig.extendFunction('findWidget', () => ({}));
    Twig.extendFunction('functionExists', () => false);
    Twig.extendFunction('generatePath', (url?: string) => url || '#');
    Twig.extendFunction('render', () => '');
    Twig.extendFunction('configurationValue', () => '');
    Twig.extendFunction('configurationValues', () => ({}));
    Twig.extendFunction('getCartQuantity', () => 0);
    Twig.extendFunction('getPriceMode', () => 'GROSS_MODE');
    Twig.extendFunction('getNumberFormatConfig', () => ({
        // eslint-disable-next-line camelcase
        grouping_separator_symbol: ',',
        // eslint-disable-next-line camelcase
        decimal_separator_symbol: '.',
        // eslint-disable-next-line camelcase
        fraction_digits: 2,
    }));
    Twig.extendFunction('currencyIsoCode', () => 'EUR');
    Twig.extendFunction('form_start', () => '<form>');
    Twig.extendFunction('form_end', () => '</form>');
    Twig.extendFunction('form_row', () => '');
    Twig.extendFunction('form_widget', () => '');
    Twig.extendFunction('form_errors', () => '');

    Twig.extendFilter('trans', (value: string, params?: unknown[]) => {
        let translated = activeTranslations[value] || value;
        if (params?.[0] && typeof params[0] === 'object' && params[0] !== null) {
            for (const [placeholder, replacement] of Object.entries(params[0] as Record<string, string>)) {
                translated = translated.split(placeholder).join(replacement);
            }
        }
        return translated;
    });
    Twig.extendFilter('money', (v: number | string | null | undefined) => {
        if (v == null) return '';
        const num = typeof v === 'number' ? v / 100 : parseFloat(String(v));
        return isNaN(num) ? v : `€${num.toFixed(2)}`;
    });
    Twig.extendFilter('moneyRaw', (v: number | string | null | undefined) => {
        if (v == null) return '';
        const num = typeof v === 'number' ? v / 100 : parseFloat(String(v));
        return isNaN(num) ? v : num.toFixed(2);
    });
    Twig.extendFilter('trimLocale', (v: unknown) => v);
    Twig.extendFilter('executeFilterIfExists', (v: unknown) => v);
    Twig.extendFilter('raw', (v: unknown) => v);
    Twig.extendFilter('sb_map_first', (value: unknown) =>
        Array.isArray(value)
            ? value.map((item: unknown) => (typeof item === 'string' && item.length ? item[0] : ''))
            : value,
    );

    initialized = true;
}

export function setTranslations(translations: Record<string, string>): void {
    activeTranslations = translations;
}

export function clearTranslations(): void {
    activeTranslations = {};
}

export const failedTemplates: FailedTemplate[] = [];

// twig.js doesn't expose `Twig.Templates` on its public export; reach through
// `Twig.extend(fn)` to grab the internal registry so we can force-overwrite
// duplicate ids (vendor first → Pyz second).
let templateRegistry: Record<string, TwigTemplate> | null = null;
Twig.extend((TwigInternalRef: TwigInternal) => {
    templateRegistry = TwigInternalRef.Templates?.registry ?? null;
});
if (typeof window !== 'undefined') {
    (window as Window & { __TWIG_REGISTRY__?: typeof templateRegistry; __TWIG__?: typeof Twig }).__TWIG_REGISTRY__ =
        templateRegistry;
    (window as Window & { __TWIG__?: typeof Twig }).__TWIG__ = Twig;
}

interface TwigInnerToken {
    type?: string;
    blockName?: string;
    output?: TwigOuterToken[];
    cases?: Array<{ output?: TwigOuterToken[] }>;
}

interface TwigOuterToken {
    type?: string;
    token?: TwigInnerToken;
}

interface TwigBlock {
    template: unknown;
    token: TwigInnerToken;
}

interface TwigTemplateInternal {
    blocks: { defined: Record<string, TwigBlock> };
    tokens: TwigOuterToken[];
}

let TwigBlockCtor: (new (template: unknown, token: TwigInnerToken) => TwigBlock) | null = null;
Twig.extend((ref: { Block?: new (t: unknown, tk: TwigInnerToken) => TwigBlock }) => {
    TwigBlockCtor = ref.Block ?? null;
});

// Walk the parsed token tree and register every nested `{% block %}` into the
// template's `blocks.defined` map. Twig.js only registers a block when the
// containing block is rendered, so when Pyz overrides `body` the nested
// `imageContainer` / `actionsContainer` blocks declared in vendor never get
// registered — and `parent()` from a Pyz nested block then resolves to
// undefined. Pre-hoisting makes those declarations visible at file scope.
function hoistNestedBlocks(template: TwigTemplateInternal): void {
    if (!TwigBlockCtor) return;
    const Ctor = TwigBlockCtor;
    const visit = (outerTokens: TwigOuterToken[] | undefined): void => {
        if (!outerTokens) return;
        for (const outer of outerTokens) {
            if (outer.type !== 'logic' || !outer.token) continue;
            const inner = outer.token;
            if (inner.type === 'Twig.logic.type.block' && inner.blockName) {
                if (!template.blocks.defined[inner.blockName]) {
                    template.blocks.defined[inner.blockName] = new Ctor(template, inner);
                }
            }
            visit(inner.output);
            if (inner.cases) {
                for (const c of inner.cases) visit(c.output);
            }
        }
    };
    visit(template.tokens);
}

function registerTemplate(id: string, source: string): void {
    const processed = preprocess(source);
    try {
        if (templateRegistry) delete templateRegistry[id];
        Twig.twig({ id, data: processed, allowInlineIncludes: true, rethrow: true });
        const tpl = templateRegistry?.[id] as unknown as TwigTemplateInternal | undefined;
        if (tpl) hoistNestedBlocks(tpl);
    } catch (err) {
        const reason = err instanceof Error ? err.message : String(err);
        failedTemplates.push({ id, reason });
        console.warn(`[Storybook Twig] Skipped template "${id}": ${reason}`);
    }
}

export function registerSingleTemplate(id: string, source: string): void {
    registerTemplate(id, source);
}

export function loadTemplates(
    requireContext: __WebpackModuleApi.RequireContext,
    namespace: string,
    vendorModuleRef?: string,
    skipNamespaceForPaths?: Set<string>,
): void {
    requireContext.keys().forEach((key) => {
        const source = requireContext(key) as string;
        const relativePath = key.slice(1);
        // When `skipNamespaceForPaths` contains this template's path, Pyz already
        // owns the `@${namespace}…` id — don't overwrite it with vendor.
        if (!skipNamespaceForPaths || !skipNamespaceForPaths.has(relativePath)) {
            registerTemplate(`@${namespace}${relativePath}`, source);
        }
        if (vendorModuleRef) {
            registerTemplate(`@${vendorModuleRef}${relativePath}`, source);
        }
    });
}

export function renderTemplate(templateId: string, context?: Record<string, unknown>): string {
    return Twig.twig({ ref: templateId }).render({
        required: REQUIRED_VALUE,
        app: {
            user: null,
            request: { pathInfo: '/', queryString: '', requestUri: '/', uri: '/' },
            locale: 'en_US',
        },
        ...context,
    });
}
