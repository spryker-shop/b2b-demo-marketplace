const fs = require('fs');
const path = require('path');
const { join } = require('path');
const StyleDictionary = require('style-dictionary').default;

function normKey(s) {
    return String(s)
        .trim()
        .replace(/\s*\.\s*/g, '.')
        .replace(/\s+/g, ' ')
        .replace(/&/g, 'and')
        .replace(/[^\w.\- ]+/g, '')
        .trim()
        .replace(/\./g, ' ');
}

function toCamel(s) {
    const parts = normKey(s).split(' ').filter(Boolean);

    if (!parts.length) return '';

    const [head, ...rest] = parts;

    return (
        head.toLowerCase() +
        rest.map(word => word ? word[0].toUpperCase() + word.slice(1).toLowerCase() : '').join('')
    );
}

function normRefValue(v) {
    if (typeof v !== 'string') return v;

    return v.replace(/\{([^}]+)\}/g, (_, path) => {
        const segs = path.split('.').map(x => x.trim()).filter(Boolean);
        const out = segs.map(s => {
            const trimmed = s.trim().replace(/\s+/g, ' ');

            if (/^\d+$/.test(trimmed)) return trimmed;

            return toCamel(trimmed);
        });

        return `{${out.join('.')}}`;
    });
}

function convert(node) {
    if (node && typeof node === 'object' && !Array.isArray(node)) {
        if ('$value' in node || '$type' in node) {
            const out = {};
            if ('$value' in node) out.value = normRefValue(node.$value);

            if ('$type' in node) out.type = node.$type;

            for (const [k, v] of Object.entries(node)) {
                if (k === '$value' || k === '$type') continue;

                out[k] = v;
            }

            return out;
        }

        const output = {};

        for (const [k, v] of Object.entries(node)) {
            const normalized = toCamel(k);

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

    const outputDir = path.dirname(outputPath);
    if (!fs.existsSync(outputDir)) {
        fs.mkdirSync(outputDir, { recursive: true });
    }

    fs.writeFileSync(outputPath, JSON.stringify(converted, null, 2) + '\n', 'utf8');
    console.log(`✔ Normalized design tokens: ${outputPath}`);
};


const buildDesignTokens = async (appSettings) => {
    const sourceTokensPath = join(appSettings.context, appSettings.paths.assets.globalAssets, 'design-tokens/design-tokens.json');
    const normalizedTokensPath = join(appSettings.context, appSettings.paths.assets.globalAssets, 'design-tokens/design-tokens-normalized.json');
    normalizeDesignTokens(sourceTokensPath, normalizedTokensPath);

    const buildPath = join(appSettings.find.shopUiEntryPoints.dirs.find(dir => !dir.includes('vendor')), `ShopUi/Theme/${appSettings.theme}/styles/`);
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
                            outputReferences: true
                        }
                    }
                ]
            }
        }
    });
    await sd.buildAllPlatforms();

    return cssFilePath;
};

module.exports = { buildDesignTokens };
