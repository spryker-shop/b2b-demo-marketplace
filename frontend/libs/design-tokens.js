/* eslint-disable no-undef */
/* eslint-disable max-lines */
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
        .replace(/^-+|-+$/g, '')
        .replace(/-/g, ' ')
        .trim()
        .replace(/\./g, ' ');
}

function toCamelCase(word) {
    const normalized = normalizeKey(word);
    const parts = normalized.split(' ').filter(Boolean);

    if (!parts.length) return '';

    if (parts.length === 1) return parts[0].toLowerCase();

    const [head, ...rest] = parts;

    return (
        head.toLowerCase() +
        rest.map((word) => (word ? word[0].toUpperCase() + word.slice(1).toLowerCase() : '')).join('')
    );
}

function normalizeRefValue(value) {
    if (typeof value === 'string') {
        return value.replace(/\{([^}]+)\}/g, (_, path) => {
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

    if (value && typeof value === 'object' && !Array.isArray(value)) {
        const result = {};

        for (const [key, val] of Object.entries(value)) {
            result[key] = normalizeRefValue(val);
        }

        return result;
    }

    return value;
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

        for (const [key, val] of Object.entries(node)) {
            const normalized = toCamelCase(key);

            if (!normalized || output[normalized]) continue;

            output[normalized] = convert(val);
        }

        return output;
    }
    return node;
}

function expandCompositeTokens(obj, parentPath = []) {
    const isPlainObject = (v) => v && typeof v === 'object' && !Array.isArray(v);
    const TYPOGRAPHY_TYPE_MAP = {
        fontFamily: 'fontFamilies',
        fontWeight: 'fontWeights',
        fontSize: 'dimension',
        lineHeight: 'lineHeights',
        letterSpacing: 'letterSpacing',
    };
    const result = {};

    for (const [key, value] of Object.entries(obj)) {
        const path = [...parentPath, key];

        if (!isPlainObject(value)) continue;

        if ('value' in value && 'type' in value) {
            if (value.type === 'typography' && isPlainObject(value.value)) {
                for (const [subKey, subValue] of Object.entries(value.value)) {
                    result[[...path, subKey].join('.')] = {
                        value: subValue,
                        type: TYPOGRAPHY_TYPE_MAP[subKey] ?? 'other',
                    };
                }
            } else {
                result[path.join('.')] = value;
            }

            continue;
        }

        Object.assign(result, expandCompositeTokens(value, path));
    }

    return result;
}

const normalizeDesignTokens = (sourcePath, outputPath) => {
    const raw = JSON.parse(fs.readFileSync(sourcePath, 'utf8'));
    const converted = convert(raw);

    const flattened = {};

    for (const [key, value] of Object.entries(converted)) {
        const flattenPatterns = ['primitives', 'colour', 'spacingdefault'];

        if (flattenPatterns.some((pattern) => key.startsWith(pattern))) {
            Object.assign(flattened, value);
        } else {
            flattened[key] = value;
        }
    }

    const expanded = expandCompositeTokens(flattened);
    const final = {};

    for (const [path, token] of Object.entries(expanded)) {
        const parts = path.split('.');
        let current = final;
        for (let i = 0; i < parts.length - 1; i++) {
            if (!current[parts[i]]) current[parts[i]] = {};
            current = current[parts[i]];
        }
        current[parts[parts.length - 1]] = token;
    }

    const outputDir = path.dirname(outputPath);
    if (!fs.existsSync(outputDir)) {
        fs.mkdirSync(outputDir, { recursive: true });
    }

    fs.writeFileSync(outputPath, JSON.stringify(final, null, 2) + '\n', 'utf8');
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

    StyleDictionary.registerTransform({
        name: 'name/kebab-clean',
        type: 'name',
        transform: (token) => {
            const parts = (token.path ?? [])
                .map((part) => String(part))
                .map((part) => part.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase())
                .map((kebab) => kebab.replace(/default/gi, ''))
                .map((kebab) => kebab.replace(/-+/g, '-').replace(/^-|-$/g, ''))
                .filter(Boolean);

            return parts.join('-');
        },
    });

    StyleDictionary.registerTransform({
        name: 'value/px-force',
        type: 'value',
        filter: (token) => {
            const dimensionTypes = ['dimension', 'fontSizes', 'borderRadius', 'spacing', 'borderWidth', 'sizing'];

            if (dimensionTypes.includes(token.type)) {
                return true;
            }

            if (token.type === 'number' && token.path) {
                const pathStr = token.path.join('.');
                const relevantKeywords = ['radius', 'spacing', 'strokeWidth', 'space', 'shadow', 'focus'];

                return relevantKeywords.some((keyword) => pathStr.includes(keyword));
            }

            return false;
        },
        transform: (token) => {
            const val = parseFloat(token.value);
            return isNaN(val) ? token.value : `${val}px`;
        },
    });

    const sd = new StyleDictionary({
        log: {
            verbosity: 'silent',
            warnings: 'disabled',
            errors: 'error',
        },
        source: [normalizedTokensPath],
        platforms: {
            css: {
                buildPath,
                transforms: ['attribute/cti', 'name/kebab-clean', 'value/px-force', 'time/seconds', 'color/css'],
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

    console.log(`Built design tokens CSS: ${cssFilePath}`);

    return cssFilePath;
};

module.exports = { buildDesignTokens };
