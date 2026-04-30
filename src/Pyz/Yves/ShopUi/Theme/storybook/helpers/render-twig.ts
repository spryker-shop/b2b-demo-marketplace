import {
    initTwigEngine,
    loadTemplates,
    registerSingleTemplate,
    renderTemplate,
    failedTemplates,
    setTranslations,
    clearTranslations,
} from '../twig/engine';

export { setTranslations, clearTranslations };

declare const require: NodeRequire & {
    context: (
        directory: string,
        useSubdirectories: boolean,
        regExp: RegExp,
    ) => __WebpackModuleApi.RequireContext;
};

declare const __WIDGET_MAP__: Record<string, string>;

let templatesLoaded = false;

function ensureTemplatesLoaded(): void {
    if (templatesLoaded) return;

    initTwigEngine();

    // Register vendor first under `@ShopUi` and `@@SprykerShop:ShopUi`,
    // then register Pyz under `@ShopUi` again. The engine clears the registry
    // entry before each registration, so Pyz wins for public lookups while
    // vendor remains reachable under its module-prefixed id.
    const vendorContext = require.context(
        '../../../../../../../vendor/spryker-shop/shop-ui/src/SprykerShop/Yves/ShopUi/Theme/default',
        true,
        /\.twig$/,
    );
    loadTemplates(vendorContext, 'ShopUi', '@SprykerShop:ShopUi');

    const pyzContext = require.context(
        '../../default',
        true,
        /\.twig$/,
    );
    loadTemplates(pyzContext, 'ShopUi');

    // Vendor widget templates from spryker-shop: register every twig as
    // `@ModuleName/<inner-path>` so `{% widget %}` rewrites that point at
    // `@ProductReviewWidget/views/.../tpl.twig` resolve. Skip ShopUi itself —
    // it's already loaded above with Pyz overrides on top.
    const allVendorContext = require.context(
        '../../../../../../../vendor/spryker-shop',
        true,
        /\/Theme\/default\/.+\.twig$/,
    );
    allVendorContext.keys().forEach((key: string) => {
        const m = key.match(/Yves\/(\w+)\/Theme\/default\/(.+)$/);
        if (!m) return;
        const moduleName = m[1];
        if (moduleName === 'ShopUi') return;
        const innerPath = m[2];
        const source = allVendorContext(key) as string;
        registerSingleTemplate(`@${moduleName}/${innerPath}`, source);
        // Also register under `@@SprykerShop:ModuleName/...` so Pyz overrides
        // that `extends molecule('x', '@SprykerShop:ProductLabelWidget')` can
        // still find vendor.
        registerSingleTemplate(`@@SprykerShop:${moduleName}/${innerPath}`, source);
    });

    // Vendor widget templates from spryker-feature: same pattern. Spryker's
    // self-service-portal and other "feature" modules ship their twigs here
    // and their Pyz overrides extend `view('x', '@SprykerFeature:Module')`,
    // so we have to register both `@Module/...` and the namespaced variant.
    const featureVendorContext = require.context(
        '../../../../../../../vendor/spryker-feature',
        true,
        /\/Theme\/default\/.+\.twig$/,
    );
    featureVendorContext.keys().forEach((key: string) => {
        const m = key.match(/Yves\/(\w+)\/Theme\/default\/(.+)$/);
        if (!m) return;
        const moduleName = m[1];
        const innerPath = m[2];
        const source = featureVendorContext(key) as string;
        registerSingleTemplate(`@${moduleName}/${innerPath}`, source);
        registerSingleTemplate(`@@SprykerFeature:${moduleName}/${innerPath}`, source);
    });

    // Pyz widget-module twig overrides — same pattern as ShopUi above:
    // re-register under `@ModuleName/...` so Pyz wins, and additionally under
    // `@@SprykerShop:ModuleName/...` so child templates that use
    // `extends molecule('x', '@SprykerShop:ProductLabelWidget')` still find vendor.
    const pyzWidgetTwigs = require.context(
        '../../../..',
        true,
        /^\.\/(?!ShopUi)\w+\/Theme\/default\/.+\.twig$/,
    );
    pyzWidgetTwigs.keys().forEach((key: string) => {
        const m = key.match(/^\.\/(\w+)\/Theme\/default\/(.+)$/);
        if (!m) return;
        const moduleName = m[1];
        const innerPath = m[2];
        const source = pyzWidgetTwigs(key) as string;
        registerSingleTemplate(`@${moduleName}/${innerPath}`, source);
    });

    if (typeof window !== 'undefined') {
        const w = window as Window & {
            __STORYBOOK_FAILED_TEMPLATES__?: typeof failedTemplates;
            __WIDGET_MAP__?: Record<string, string>;
        };
        w.__STORYBOOK_FAILED_TEMPLATES__ = failedTemplates;
        w.__WIDGET_MAP__ = typeof __WIDGET_MAP__ !== 'undefined' ? __WIDGET_MAP__ : {};
    }

    templatesLoaded = true;
}

export function renderAtom(name: string, context?: Record<string, unknown>): string {
    ensureTemplatesLoaded();
    return renderTemplate(`@ShopUi/components/atoms/${name}/${name}.twig`, context);
}

export function renderMolecule(name: string, context?: Record<string, unknown>): string {
    ensureTemplatesLoaded();
    return renderTemplate(`@ShopUi/components/molecules/${name}/${name}.twig`, context);
}

export function renderOrganism(name: string, context?: Record<string, unknown>): string {
    ensureTemplatesLoaded();
    return renderTemplate(`@ShopUi/components/organisms/${name}/${name}.twig`, context);
}

// Escape hatch for stories that need to render an arbitrary registered template
// (e.g. a Spryker `view` from a widget module). Prefer `renderAtom` / `renderMolecule`
// / `renderOrganism` when the path matches their pattern.
export function renderTemplateById(templateId: string, context?: Record<string, unknown>): string {
    ensureTemplatesLoaded();
    return renderTemplate(templateId, context);
}
