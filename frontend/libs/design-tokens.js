/* eslint-disable no-console */
/* eslint-disable no-undef */
const StyleDictionary = require('style-dictionary').default;
const { join } = require('path');
const { readFileSync, writeFileSync } = require('fs');

const REF_RULES = [
    { test: (r) => r.startsWith('Primitives.') || r.startsWith('Typography.'), map: (r) => r },
    { test: (r) => r.startsWith('primitive.typography'), map: (r) => `Typography.${r}` },
    { test: (r) => !r.includes('.'), map: (r) => r },
];

function normalizeRefString(str) {
    if (typeof str !== 'string') return str;

    const match = str.match(/^\{(.+)\}$/);

    if (!match) return str;

    const ref = match[1];
    const rule = REF_RULES.find((x) => x.test(ref));
    const normalized = rule ? rule.map(ref) : `Primitives.${ref}`;

    return `{${normalized}}`;
}

function normalizeKey(key) {
    const [first, second] = String(key).split('/');
    if (!second) return first;

    const s = second.toLowerCase();
    return ['default', 'mode'].some((p) => s.startsWith(p)) ? first : key;
}

function normalizeTokens(node) {
    if (!node || typeof node !== 'object') return node;

    if ('value' in node) {
        const value = normalizeRefString(node.value);

        if (value && typeof value === 'object') return null;

        return { ...node, value };
    }

    const out = {};

    for (const [k, v] of Object.entries(node)) {
        const nk = normalizeKey(k);
        const nv = normalizeTokens(v);

        if (nv !== null) out[nk] = nv;
    }

    return out;
}

StyleDictionary.registerTransform({
    name: 'name/kebab-custom',
    type: 'name',
    transform: ({ path }) => {
        const p = (path ?? []).map((s) => String(s).toLowerCase().replace(/\s+/g, '-').replace(/_/g, '-'));

        const parts = p[0] === 'primitives' || p[0] === 'typography' ? p.slice(1) : p;

        return parts
            .join('-')
            .replace('semantic-colour-', '')
            .replace('semantic-typography-', '')
            .replace('semantic-', '')
            .replace('spacing-space-', 'space-');
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

    console.info(`Temp tokens saved at: ${tempTokensPath}`);
    console.info(`Built design tokens CSS: ${cssFilePath}`);

    return cssFilePath;
};

module.exports = { buildDesignTokens };
