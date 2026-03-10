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
    filter: ({ value, type, path }) => {
        if (typeof value !== 'number' || type === 'color' || type === 'string') return false;

        const p = (path ?? []).join('.').toLowerCase();
        return !p.includes('weight') && !p.includes('letterspacing');
    },
    transform: ({ value }) => `${value}px`,
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
                transforms: ['attribute/cti', 'name/kebab-custom', 'value/px-custom', 'color/css'],
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
