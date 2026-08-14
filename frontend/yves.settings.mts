import {
    defineConfig,
    resolveSourceLayout,
} from '../vendor/spryker-shop/shop-ui/src/SprykerShop/Yves/ShopUi/FrontendBuilder/settings.mts';

const sourceLayout = resolveSourceLayout();

/**
 * Demo-only frontend build wiring for `master-demo`.
 *
 * The AI Commerce demo features ship their Yves assets under `src/Demo/Yves` (search-by-image,
 * cost-price) plus a demo icon-sprite. `src/Demo` shadows `src/Pyz`, so both the component source
 * dirs and the sprite sources need the Demo entry listed AFTER the Pyz one: `paths.sources` key
 * order is load-bearing in the builder ("later entry wins"), and the sprite sources are merged in
 * order too. This replaces the equivalent wiring that lived in the pre-2.0 `frontend/settings.js`,
 * which the shop-ui 2.0 FrontendBuilder migration removed.
 */
export default defineConfig({
    paths: {
        sources: {
            ...sourceLayout.sources,
            demoProject: './src/Demo/Yves',
        },
        iconSprite: {
            sources: [
                './src/Demo/Yves/ShopUi/Theme/default/components/atoms/icon-sprite/icon-sprite.twig',
                ...sourceLayout.iconSpriteSources,
            ],
        },
    },
});
