const fs = require('fs');
const path = require('path');
const StyleDictionary = require('style-dictionary').default;
const { join } = require('path');

function normalizeKey(s) {
    return String(s)
        .trim()
        .replace(/\s*\.\s*/g, '.')
        .replace(/\s+/g, ' ')
        .replace(/&/g, 'and')
        .replace(/[^\w.\- ]+/g, '')
        .trim()
        .replace(/\./g, ' ');
}

function toCamelCase(s) {
    const normalized = normalizeKey(s);
    const parts = normalized.split(' ').filter(Boolean);

    if (!parts.length) return '';

    // If there's only one part and no spaces in original, return as-is (already camelCase)
    if (parts.length === 1 && !String(s).includes(' ') && !String(s).includes('/')) {
        return s;
    }

    const [head, ...rest] = parts;

    return (
        head.toLowerCase() +
        rest.map((word) => (word ? word[0].toUpperCase() + word.slice(1).toLowerCase() : '')).join('')
    );
}

function normalizeRefValue(v) {
    if (typeof v !== 'string') return v;

    return v.replace(/\{([^}]+)\}/g, (_, path) => {
        // Remove extra spaces around dots
        const cleanPath = path.replace(/\s*\.\s*/g, '.');
        const segs = cleanPath
            .split('.')
            .map((x) => x.trim())
            .filter(Boolean);
        const out = segs.map((s) => {
            const trimmed = s.trim().replace(/\s+/g, ' ');

            if (/^\d+$/.test(trimmed)) return trimmed;

            return toCamelCase(trimmed);
        });

        return `{${out.join('.')}}`;
    });
}

function convert(node) {
    if (node && typeof node === 'object' && !Array.isArray(node)) {
        const hasValue = '$value' in node || 'value' in node;
        const hasType = '$type' in node || 'type' in node;

        if (hasValue || hasType) {
            const out = {};

            if ('$value' in node || 'value' in node) {
                out.value = normalizeRefValue(node.$value || node.value);
            }

            if ('$type' in node || 'type' in node) {
                out.type = node.$type || node.type;
            }

            for (const [k, v] of Object.entries(node)) {
                if (k === '$value' || k === '$type' || k === 'value' || k === 'type') continue;

                out[k] = v;
            }

            return out;
        }

        const output = {};

        for (const [k, v] of Object.entries(node)) {
            const normalized = toCamelCase(k);

            if (!normalized) continue;

            output[normalized] = convert(v);
        }

        return output;
    }
    return node;
}

const normalizeDesignTokens = (sourcePath, outputPath) => {
    const raw = JSON.parse(fs.readFileSync(sourcePath, 'utf8'));
    const converted = convert(raw);

    const flattened = {};

    for (const [key, value] of Object.entries(converted)) {
        if (key.startsWith('primitives') || key.startsWith('colourUsage')) {
            Object.assign(flattened, value);
        } else {
            flattened[key] = value;
        }
    }

    const outputDir = path.dirname(outputPath);
    if (!fs.existsSync(outputDir)) {
        fs.mkdirSync(outputDir, { recursive: true });
    }

    fs.writeFileSync(outputPath, JSON.stringify(flattened, null, 2) + '\n', 'utf8');
    console.log(`Normalized design tokens: ${outputPath}`);
};

const buildDesignTokens = async (appSettings) => {
    const sourceTokensPath = join(
        appSettings.context,
        appSettings.paths.assets.globalAssets,
        'design-tokens/design-tokens.json',
    );
    const normalizedTokensPath = join(
        appSettings.context,
        appSettings.paths.assets.globalAssets,
        'design-tokens/design-tokens-normalized.json',
    );
    normalizeDesignTokens(sourceTokensPath, normalizedTokensPath);

    const buildPath = join(
        appSettings.find.shopUiEntryPoints.dirs.find((dir) => !dir.includes('vendor')),
        `ShopUi/Theme/${appSettings.theme}/styles/`,
    );
    const cssFilePath = join(buildPath, 'design-tokens.css');

    const sd = new StyleDictionary({
        source: [normalizedTokensPath],
        platforms: {
            css: {
                transformGroup: 'css',
                buildPath,
                files: [
                    {
                        destination: 'design-tokens.css',
                        format: 'css/variables',
                        options: {
                            selector: ':root',
                            outputReferences: true,
                        },
                    },
                ],
            },
        },
    });
    await sd.buildAllPlatforms();

    return cssFilePath;
};

module.exports = { buildDesignTokens };
