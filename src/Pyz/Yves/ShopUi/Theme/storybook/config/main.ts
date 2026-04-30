import fs from 'node:fs';
import path from 'node:path';

import glob from 'fast-glob';
import webpack from 'webpack';
import autoprefixer from 'autoprefixer';
import * as sass from 'sass';

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
const shopUiVendor = path.resolve(projectRoot, 'vendor/spryker-shop/shop-ui/src/SprykerShop/Yves/ShopUi/Theme/default');
const shopUiPyz = path.resolve(projectRoot, 'src/Pyz/Yves/ShopUi/Theme/default');

export default {
    stories: [
        path.join(projectRoot, 'src/Pyz/Yves/ShopUi/Theme/default/**/*.stories.{js,ts}'),
    ],

    framework: '@storybook/html-webpack5',

    addons: ['@storybook/addon-essentials'],

    staticDirs: [
        { from: path.join(projectRoot, 'frontend/assets/global/default/icons'), to: '/icons' },
        { from: path.join(projectRoot, 'frontend/assets/global/default/images'), to: '/images' },
    ],

    async webpackFinal(config) {
        const sharedScss = path.resolve(shopUiPyz, 'styles/shared.scss');

        const vendorStyleDirs = [
            path.join(projectRoot, 'vendor/spryker-shop'),
            path.join(projectRoot, 'vendor/spryker-feature'),
            path.join(projectRoot, 'vendor/spryker'),
        ];

        const vendorStylePatterns = vendorStyleDirs.flatMap((dir) => [
            path.join(dir, '**/Theme/default/components/atoms/*/*.scss'),
            path.join(dir, '**/Theme/default/components/molecules/*/*.scss'),
            path.join(dir, '**/Theme/default/components/organisms/*/*.scss'),
            path.join(dir, '**/Theme/default/templates/*/*.scss'),
            path.join(dir, '**/Theme/default/views/*/*.scss'),
        ]);

        const vendorStyles = await glob(vendorStylePatterns, {
            ignore: ['**/style.scss', '**/node_modules/**'],
            absolute: true,
        });

        const allResources = [sharedScss, ...vendorStyles];

        // --- Aliases from tsconfig.yves.json ---
        const tsConfig = require(path.join(projectRoot, 'tsconfig.yves.json'));
        const tsPaths = tsConfig.compilerOptions.paths || {};
        const tsAliases = {};

        for (const [aliasPattern, targets] of Object.entries<string[]>(tsPaths)) {
            if (aliasPattern === '*' || !targets.length) continue;
            const alias = aliasPattern.replace(/\/\*$/, '');
            const target = targets[0].replace(/\/\*$/, '');
            tsAliases[alias] = path.resolve(projectRoot, target);
        }

        config.resolve.alias = {
            ...config.resolve.alias,
            ...tsAliases,
            'storybook-helpers': path.resolve(projectRoot, 'src/Pyz/Yves/ShopUi/Theme/storybook/helpers'),
        };

        config.resolve.extensions = [...new Set([
            ...(config.resolve.extensions || []),
            '.ts', '.js', '.scss',
        ])];

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
                        implementation: sass,
                        api: 'legacy',
                        sassOptions: {
                            includePaths: [
                                shopUiVendor,
                                path.join(shopUiVendor, 'styles'),
                                shopUiPyz,
                                path.join(shopUiPyz, 'styles'),
                            ],
                            silenceDeprecations: ['import', 'legacy-js-api'],
                        },
                        additionalData: (content, loaderContext) => {
                            const currentFile = loaderContext.resourcePath;
                            const imports = allResources
                                .filter((resource) => resource !== currentFile)
                                .map((resource) => `@import "${resource}";`)
                                .join('\n');
                            return `${imports}\n${content}`;
                        },
                    },
                },
            ],
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
