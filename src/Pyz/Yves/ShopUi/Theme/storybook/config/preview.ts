/* eslint-disable no-console */
import '../../default/styles/design-tokens.css';
import '../../default/styles/basic.scss';
import '../../default/styles/util.scss';
import { setup, mount } from 'ShopUi/app';
import { setTranslations, clearTranslations } from 'storybook-helpers/render-twig';

declare const require: NodeJS.Require & {
    context: (directory: string, useSubdirectories: boolean, regExp: RegExp) => __WebpackModuleApi.RequireContext;
};

interface StoryContext {
    moduleExport?: { translations?: Record<string, string> };
    parameters?: { translations?: Record<string, string> };
}

type StoryFn = () => string | HTMLElement;

// --- Inline SVG sprite for #:iconName references ---
fetch('/icons/sprite.svg')
    .then((r) => r.text())
    .then((svg) => {
        const div = document.createElement('div');
        div.innerHTML = svg;
        div.style.display = 'none';
        document.body.prepend(div);
    })
    .catch(() => {});

// --- 1. Vendor components FIRST (base styles + web component registration) ---
const vendorComponents = require.context(
    '../../../../../../../vendor/spryker-shop/shop-ui/src/SprykerShop/Yves/ShopUi/Theme/default/components',
    true,
    /index\.ts$/,
);
vendorComponents.keys().forEach((key) => {
    try {
        vendorComponents(key);
    } catch (e) {
        /* swallow — vendor components without runnable index are expected */
    }
});

// --- 1b. Widget-module components (rating-selector, label-group, …)
// Each component's `index.ts` does `import './style.scss'` which is what
// invokes the mixin defined alongside it (e.g. rating-selector renders
// 10 half-step <span>s in markup; the mixin overlays them as 5 visible stars).
// Skip vendor components that have a Pyz-level override at the same relative
// path — otherwise vendor's mixin pumps out rules that don't match Pyz's
// redefined token values (e.g. icon-size: 20px vs Pyz 10px) and they conflict.
const pyzWidgetComponentsPreScan = require.context(
    '../../../..',
    true,
    /^\.\/(?!ShopUi)\w+\/Theme\/default\/components\/(atoms|molecules|organisms)\/[^/]+\/index\.ts$/,
);
const pyzWidgetRelPaths = new Set<string>();
pyzWidgetComponentsPreScan.keys().forEach((key: string) => {
    const m = key.match(/^\.\/(.+)$/);
    if (m) pyzWidgetRelPaths.add(m[1]);
});

const widgetComponents = require.context(
    '../../../../../../../vendor/spryker-shop',
    true,
    /(?<!shop-ui)\/Theme\/default\/components\/[^/]+\/[^/]+\/index\.ts$/,
);
widgetComponents.keys().forEach((key: string) => {
    const m = key.match(/Yves\/(\w+)\/Theme\/default\/(components\/.+\/index\.ts)$/);
    if (m) {
        const relPath = `${m[1]}/Theme/default/${m[2]}`;
        if (pyzWidgetRelPaths.has(relPath)) return;
    }
    try {
        widgetComponents(key);
    } catch (e) {
        /* swallow — many widget-module index.ts files only register web components */
    }
});

// --- 2. Pyz components SECOND (override styles + web component re-registration) ---
const pyzComponents = require.context('../../default/components', true, /index\.ts$/);
pyzComponents.keys().forEach((key) => {
    try {
        pyzComponents(key);
    } catch (e) {
        const message = e instanceof Error ? e.message : String(e);
        console.warn(`[Storybook] Failed to load component ${key}:`, message);
    }
});

// --- 2b. Pyz widget-module components — project overrides for vendor widget
// components (e.g. Pyz/.../ProductLabelWidget/.../label-group). Must run AFTER
// step 1b so the Pyz `style.scss` cascades over vendor's. Skip ShopUi (already
// loaded) — the require.context root is `src/Pyz/Yves`.
const pyzWidgetComponents = require.context(
    '../../../..',
    true,
    /^\.\/(?!ShopUi)\w+\/Theme\/default\/components\/(atoms|molecules|organisms)\/[^/]+\/index\.ts$/,
);
pyzWidgetComponents.keys().forEach((key: string) => {
    try {
        pyzWidgetComponents(key);
    } catch (e) {
        const message = e instanceof Error ? e.message : String(e);
        console.warn(`[Storybook] Failed to load Pyz widget component ${key}:`, message);
    }
});

setup({
    name: 'storybook',
    isProduction: false,
    events: {
        mount: 'components-mount',
        ready: 'components-ready',
        bootstrap: 'application-bootstrap',
        error: 'application-error',
        upgrade: 'components-upgrade',
    },
    log: {
        prefix: 'storybook',
        level: 2,
    },
});

// --- Decorator: mount Spryker web components after each story render ---
export const decorators = [
    (storyFn: StoryFn, context: StoryContext) => {
        const translations = context.moduleExport?.translations || context.parameters?.translations || {};
        setTranslations(translations);

        const html = storyFn();

        clearTranslations();

        requestAnimationFrame(() => {
            mount().catch((err: unknown) => {
                const message = err instanceof Error ? err.message : String(err);
                console.warn('[Storybook] mount error:', message);
            });

            // lazy-image uses IntersectionObserver to set background-image from data attr.
            // In Storybook the observer may not fire — apply immediately.
            document.querySelectorAll<HTMLElement>('[data-background-image]').forEach((el) => {
                if (el.dataset.backgroundImage) {
                    el.style.backgroundImage = el.dataset.backgroundImage;
                }
            });
            document.querySelectorAll<HTMLImageElement>('[data-src]').forEach((img) => {
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                }
            });
        });

        return html;
    },
];

export const parameters = {
    layout: 'padded',
    controls: {
        matchers: {
            color: /(background|color)$/i,
            date: /Date$/i,
        },
    },
    options: {
        storySort: {
            order: [
                'Introduction',
                'Basic',
                ['Colors', 'Typography', 'Spacing', 'Grid'],
                'Atoms',
                'Molecules',
                'Organisms',
                'Templates',
                'Views',
            ],
        },
    },
};
