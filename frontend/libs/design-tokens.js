const StyleDictionary = require('style-dictionary').default;
const { join } = require('path');
const { readFileSync, writeFileSync } = require('fs');

const normalizeTokens = (x, path = []) => {
    if (!x || typeof x !== 'object') return x;

    if (x.value !== undefined) {
        let value = x.value;

        if (typeof value === 'string' && value.startsWith('{') && value.endsWith('}')) {
            const refContent = value.slice(1, -1);

            let normalizedRef = refContent;

            if (!normalizedRef.includes('.')) {
                normalizedRef = refContent;
            } else if (refContent.startsWith('primitive.typography')) {
                normalizedRef = `Typography.${refContent}`;
            } else if (!refContent.startsWith('Primitives.') && !refContent.startsWith('Typography.')) {
                normalizedRef = `Primitives.${refContent}`;
            }

            value = `{${normalizedRef}}`;
        }

        if (typeof value === 'object') {
            return null;
        }

        return { ...x, value };
    }

    const entries = Object.entries(x).map(([k, v]) => {
        let normalizedKey = k;
        const parts = k.split('/');
        if (parts.length > 1) {
            const secondPart = parts[1]?.toLowerCase();
            if (secondPart?.startsWith('default') || secondPart?.startsWith('mode')) {
                normalizedKey = parts[0];
            }
        }

        const normalized = normalizeTokens(v, [...path, normalizedKey]);
        return normalized === null ? null : [normalizedKey, normalized];
    }).filter(Boolean);

    return Object.fromEntries(entries);
};

StyleDictionary.registerTransform({
    name: 'name/kebab-custom',
    type: 'name',
    transform: ({ path }) => {
        const p = path.map((s) => String(s).toLowerCase().replace(/\s+/g, '-').replace(/_/g, '-'));
        const parts = p[0] === 'primitives' || p[0] === 'typography' ? p.slice(1) : p;

        return parts.join('-')
            .replace('semantic-colour-', '')
            .replace('semantic-typography-', '')
            .replace('semantic-', '');
    },
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
    const tempTokensPath = join(assetsRoot, 'design-tokens/design-tokens-normalized.json');

    const baseEntry = appSettings.find.shopUiEntryPoints.dirs.find((d) => !d.includes('vendor'));
    const buildPath = join(baseEntry, `ShopUi/Theme/${appSettings.theme}/styles/`);
    const cssFilePath = join(buildPath, 'design-tokens.css');

    const tokens = JSON.parse(readFileSync(sourceTokensPath, 'utf8'));
    const normalized = normalizeTokens(tokens);
    writeFileSync(tempTokensPath, JSON.stringify(normalized, null, 2));

    await new StyleDictionary({
        log: { verbosity: 'silent', warnings: 'disabled', errors: 'error' },
        source: [tempTokensPath],
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

    console.log(`Temp tokens saved at: ${tempTokensPath}`);
    console.log(`Built design tokens CSS: ${cssFilePath}`);

    return cssFilePath;
};

module.exports = { buildDesignTokens };
