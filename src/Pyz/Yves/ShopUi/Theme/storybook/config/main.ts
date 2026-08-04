import { createHash } from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import glob from 'fast-glob';
import webpack from 'webpack';
import autoprefixer from 'autoprefixer';
import * as sass from 'sass-embedded';

import {
    createErrorTranslatingSassImplementation,
    createSassInjectionImporterFactory,
    isInjectableComponentPath,
    isInjectableStyleRootPath,
} from '../../../../../../../vendor/spryker-shop/shop-ui/src/SprykerShop/Yves/ShopUi/FrontendBuilder/libs/sass/sass-injection-importer.mts';
import { createMixinIndex } from '../../../../../../../vendor/spryker-shop/shop-ui/src/SprykerShop/Yves/ShopUi/FrontendBuilder/libs/sass/mixin-index.mts';
import { getFilteredNamespaceConfigList } from '../../../../../../../vendor/spryker-shop/shop-ui/src/SprykerShop/Yves/ShopUi/FrontendBuilder/libs/sass/namespace-config-parser.mts';
import { getAliasList } from '../../../../../../../vendor/spryker-shop/shop-ui/src/SprykerShop/Yves/ShopUi/FrontendBuilder/libs/webpack/alias.mts';
import {
    findAppEntryPoint,
    findComponentStyles,
} from '../../../../../../../vendor/spryker-shop/shop-ui/src/SprykerShop/Yves/ShopUi/FrontendBuilder/libs/webpack/finder.mts';
import {
    getAppSettings,
    loadProjectGlobalSettings,
} from '../../../../../../../vendor/spryker-shop/shop-ui/src/SprykerShop/Yves/ShopUi/FrontendBuilder/settings.mts';

// Spryker widgets are PHP classes whose `getName()` returns the widget id used
// in `{% widget 'X' %}` calls and whose `getTemplate()` returns the twig path
// like `@ModuleName/views/.../tpl.twig`. Build a map at compile time so the
// storybook preprocessor can rewrite widgets to plain `{% include %}`.
function buildWidgetMap(root: string): Record<string, string> {
    const widgetFiles = glob.sync(
        [
            'vendor/spryker-shop/**/Widget/*Widget.php',
            'vendor/spryker-feature/**/Widget/*Widget.php',
            'vendor/spryker/**/Widget/*Widget.php',
            'src/Pyz/**/Widget/*Widget.php',
        ],
        { cwd: root, absolute: true, ignore: ['**/node_modules/**'] },
    );
    const map: Record<string, string> = {};
    for (const file of widgetFiles) {
        const src = fs.readFileSync(file, 'utf8');
        const nameMatch = src.match(/getName\s*\(\s*\)[^{]*\{[^}]*?return\s+'([^']+)'/);
        const tplMatch = src.match(/getTemplate\s*\(\s*\)[^{]*\{[^}]*?return\s+'([^']+)'/);
        if (nameMatch && tplMatch) {
            map[nameMatch[1]] = tplMatch[1];
        }
    }
    return map;
}

const projectRoot = path.resolve(__dirname, '../../../../../../..');

export default {
    stories: [path.join(projectRoot, 'src/Pyz/Yves/ShopUi/Theme/default/**/*.stories.{js,ts}')],

    framework: '@storybook/html-webpack5',

    addons: [
        {
            name: '@storybook/addon-essentials',
            options: {
                controls: false,
                actions: false,
                backgrounds: false,
                measure: false,
                outline: false,
                viewport: false,
                toolbars: false,
            },
        },
    ],

    staticDirs: [
        { from: path.join(projectRoot, 'frontend/assets/global/default/icons'), to: '/icons' },
        { from: path.join(projectRoot, 'frontend/assets/global/default/images'), to: '/images' },
    ],

    async webpackFinal(config) {
        // --- Builder inputs: the same settings `npm run yves` resolves (frontend/yves.settings.mts included) ---
        const globalSettings = await loadProjectGlobalSettings();
        const namespaceConfigPath = path.resolve(projectRoot, globalSettings.paths.namespaceConfig);
        const namespaceConfigList = getFilteredNamespaceConfigList({
            mode: 'development',
            namespaces: [],
            themes: [],
            pathToConfig: namespaceConfigPath,
            isInjectionDebuggingEnabled: false,
        });
        const [appSettings] = getAppSettings(namespaceConfigList, namespaceConfigPath, globalSettings);

        const aliasList = getAliasList(appSettings);

        config.resolve.alias = {
            ...config.resolve.alias,
            ...aliasList,
            'storybook-helpers': path.resolve(projectRoot, 'src/Pyz/Yves/ShopUi/Theme/storybook/helpers'),
        };

        config.resolve.extensions = [...new Set([...(config.resolve.extensions || []), '.ts', '.js', '.scss'])];

        // --- Sass pipeline: the ShopUi FrontendBuilder injection, same as `npm run yves` ---
        const [componentStyles, sharedStyleFilePaths, projectWrapperPath] = await Promise.all([
            findComponentStyles(appSettings.find.componentStyles),
            findComponentStyles({
                dirs: appSettings.find.componentStyles.dirs,
                patterns: ['**/Theme/*/styles/**/*.scss', '!**/__tests__/**'],
            }),
            findAppEntryPoint(appSettings.find.shopUiEntryPoints, './styles/shared.scss'),
        ]);

        const mixinIndex = await createMixinIndex({
            componentStyleFilePaths: componentStyles,
            sharedStyleFilePaths,
            isInjectionDebuggingEnabled: false,
        });

        const {
            buildInjectionBanner,
            buildStyleRootBanner,
            createImporter: createSassInjectionImporter,
        } = createSassInjectionImporterFactory({
            aliasList,
            projectWrapperPath: projectWrapperPath ?? null,
            mixinIndex,
        });

        const widgetMap = buildWidgetMap(projectRoot);

        // --- DefinePlugin for Spryker globals ---
        config.plugins.push(
            new webpack.DefinePlugin({
                __NAME__: JSON.stringify('storybook'),
                __PRODUCTION__: false,
                __WIDGET_MAP__: JSON.stringify(widgetMap),
            }),
        );

        // --- TypeScript via babel-loader (same as production build) ---
        config.module.rules.push({
            test: /\.ts$/,
            exclude: /node_modules/,
            loader: 'babel-loader',
            options: {
                cacheDirectory: true,
                presets: [
                    ['@babel/env', { loose: true, modules: false, targets: { esmodules: true }, useBuiltIns: false }],
                    '@babel/preset-typescript',
                ],
                plugins: [
                    '@babel/plugin-transform-runtime',
                    ['@babel/plugin-transform-class-properties', { loose: true }],
                ],
            },
        });

        // --- SCSS ---
        const storybookStyleLoader = require.resolve('style-loader', {
            paths: [require.resolve('@storybook/builder-webpack5')],
        });

        const scssRule = {
            test: /\.scss$/,
            use: [
                storybookStyleLoader,
                { loader: 'css-loader', options: { importLoaders: 2, url: false } },
                {
                    loader: 'postcss-loader',
                    options: { postcssOptions: { plugins: [autoprefixer] } },
                },
                {
                    loader: 'sass-loader',
                    options: {
                        implementation: createErrorTranslatingSassImplementation(sass),
                        api: 'modern-compiler',
                        additionalData: (content, loaderContext) => {
                            const isComponentRoot = isInjectableComponentPath(loaderContext.resourcePath);

                            if (!isComponentRoot && !isInjectableStyleRootPath(loaderContext.resourcePath)) {
                                return content;
                            }

                            const injectionBanner = isComponentRoot
                                ? buildInjectionBanner(content, loaderContext.resourcePath)
                                : buildStyleRootBanner(content);

                            return `${injectionBanner} ${content}`;
                        },
                        sassOptions: (loaderContext) => {
                            const sassInjectionImporter = createSassInjectionImporter({
                                onFileLoaded: (loadedFilePath) => loaderContext.addDependency(loadedFilePath),
                            });

                            return {
                                importer: sassInjectionImporter,
                                importers: [sassInjectionImporter],
                            };
                        },
                    },
                },
            ],
        };

        config.devtool = false;
        config.optimization = { ...config.optimization, minimize: false };
        config.cache = {
            type: 'filesystem',
            version: `storybook-mixin-index-${createHash('sha256').update(mixinIndex.getFingerprint()).digest('hex')}`,
        };

        config.module.rules = config.module.rules.filter(
            (rule) => !rule.test || !rule.test.toString().includes('scss'),
        );
        config.module.rules.push(scssRule);

        // --- Twig as raw strings ---
        config.module.rules.push({
            test: /\.twig$/,
            type: 'asset/source',
        });

        return config;
    },
};
