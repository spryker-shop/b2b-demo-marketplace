/* eslint-disable no-console */
/* eslint-disable no-undef */
const StyleDictionary = require('style-dictionary').default;
const { join } = require('path');

StyleDictionary.registerTransform({
    name: 'name/kebab-custom',
    type: 'name',
    transform: ({ path }) => path.slice(1).join('-'),
});

StyleDictionary.registerTransform({
    name: 'value/px-custom',
    type: 'value',
    filter: (token) => typeof (token.$value || token.value) === 'number' &&
        !token.path.join('.').toLowerCase().includes('weight'),
    transform: (token) => `${token.$value || token.value}px`,
});

const buildDesignTokens = async (appSettings) => {
    const assetsRoot = join(appSettings.context, appSettings.paths.assets.globalAssets);
    const sourceTokensPath = join(assetsRoot, 'design-tokens/design-tokens.json');

    const baseEntry = appSettings.find.shopUiEntryPoints.dirs.find((d) => !d.includes('vendor'));
    const buildPath = join(baseEntry, `ShopUi/Theme/${appSettings.theme}/styles/`);
    const cssFilePath = join(buildPath, 'design-tokens.css');

    await new StyleDictionary({
        log: { verbosity: 'silent', warnings: 'disabled', errors: 'error' },
        source: [sourceTokensPath],
        platforms: {
            css: {
                buildPath,
                transforms: ['attribute/cti', 'name/kebab-custom', 'color/css', 'value/px-custom'],
                files: [
                    {
                        destination: 'design-tokens.css',
                        format: 'css/variables',
                        options: { selector: ':root', outputReferences: true },
                    },
                ],
            },
        },
    }).buildAllPlatforms();

    console.info(`Built design tokens CSS: ${cssFilePath}`);

    return cssFilePath;
};

module.exports = { buildDesignTokens };
