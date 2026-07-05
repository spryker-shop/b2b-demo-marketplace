import { join, sep } from 'path';
import { existsSync } from 'fs';
import { createRequire } from 'module';

const require = createRequire(import.meta.url);
const glob = require('fast-glob');

let isStyleDictionaryRegistered = false;

const registerCustomTransforms = (StyleDictionary) => {
    if (isStyleDictionaryRegistered) {
        return;
    }

    StyleDictionary.registerTransform({
        name: 'name/kebab-custom',
        type: 'name',
        transform: ({ path }) => path.slice(1).join('-'),
    });

    StyleDictionary.registerTransform({
        name: 'value/px-custom',
        type: 'value',
        filter: (token) =>
            typeof (token.$value || token.value) === 'number' && !token.path.join('.').toLowerCase().includes('weight'),
        transform: (token) => `${token.$value || token.value}px`,
    });

    StyleDictionary.registerFileHeader({
        name: 'generated-header',
        fileHeader: () => {
            const now = new Date();
            const date = now.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const time = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

            return [`Generated at: ${date} ${time}`];
        },
    });

    isStyleDictionaryRegistered = true;
};

// Resolves the concrete project ShopUi theme styles directory the design-tokens CSS is written into.
const resolveStylesBuildPath = (appSettings) => {
    const projectSourcesDir = appSettings.find.shopUiEntryPoints.dirs.find((dir) => !dir.includes('vendor'));
    const stylesPattern = `ShopUi/Theme/${appSettings.theme}/styles`;
    const [resolvedDir] = glob.sync(join(projectSourcesDir, stylesPattern), {
        onlyDirectories: true,
        absolute: true,
        unique: true,
    });

    return `${resolvedDir ?? join(projectSourcesDir, stylesPattern)}${sep}`;
};

const buildDesignTokens = async (appSettings) => {
    const assetsRoot = join(appSettings.context, appSettings.paths.assets.globalAssets);
    const sourceTokensPath = join(assetsRoot, 'design-tokens/design-tokens.json');

    if (!existsSync(sourceTokensPath)) {
        return null;
    }

    let StyleDictionary = null;

    try {
        const module = await import('style-dictionary');
        StyleDictionary = module.default;
    } catch {
        console.info('Design tokens build is disabled: style-dictionary is not installed.');

        return null;
    }

    registerCustomTransforms(StyleDictionary);

    const buildPath = resolveStylesBuildPath(appSettings);
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
                        options: { selector: ':root', outputReferences: true, fileHeader: 'generated-header' },
                    },
                ],
            },
        },
    }).buildAllPlatforms();

    console.info(`Built design tokens CSS: ${cssFilePath}`);

    return cssFilePath;
};

/**
 * Project-level build hook: generates the design-tokens CSS from design-tokens.json and contributes
 * it to the critical webpack entry. No-op when the project provides no token source.
 */
export default {
    name: 'design-tokens',
    async run(appSettings) {
        const cssFilePath = await buildDesignTokens(appSettings);

        if (!cssFilePath) {
            return {};
        }

        return { critical: [cssFilePath] };
    },
};
