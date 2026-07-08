import { defineConfig } from '../vendor/spryker-shop/shop-ui/src/SprykerShop/Yves/ShopUi/builder/settings.mjs';
import assetBlacklist from './configs/asset-blacklist.mjs';
import designTokens from './libs/design-tokens.mjs';

/**
 * Project-level builder settings override.
 *
 * The frontend builder itself lives in the ShopUi module and is shared by all demoshops.
 * This file holds only the project-specific configuration:
 *
 *   - `paths.sources`  — where the builder looks for component assets to build
 *   - `assetBlacklist` — core/feature assets to exclude from this project's build
 *                        (edit ./configs/asset-blacklist.mjs)
 *   - `buildHooks`     — project build steps run before webpack assembly (e.g. design tokens).
 *                        Each hook is { name, run(appSettings) } and may contribute webpack entries;
 *                        add project-specific build steps here as new hook modules under ./libs.
 */
export default defineConfig({
    assetBlacklist,
    paths: {
        iconSprite: {
            sources: [
                './src/Pyz/Yves/ShopUi/Theme/default/components/atoms/icon-sprite/icon-sprite.twig',
                './vendor/SprykerShop/ShopUi/src/SprykerShop/Yves/ShopUi/Theme/default/components/atoms/icon-sprite/icon-sprite.twig',
            ],
            target: './frontend/assets/global/default/icons/sprite.svg',
        },
        sources: {
            namespaceConfig: './config/Yves/frontend-build-config.json',
            core: './vendor/spryker-shop',
            sprykerCore: './vendor/spryker',
            features: './vendor/spryker-feature',
            project: './src/Pyz/Yves',
        }
    },
    buildHooks: [designTokens],
});
