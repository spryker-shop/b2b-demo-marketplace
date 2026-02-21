import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const raw = JSON.parse(fs.readFileSync(path.join(__dirname, 'variables.json'), 'utf8'));

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

const converted = convert(raw);
fs.writeFileSync(path.join(__dirname, 'tokens.sd.json'), JSON.stringify(converted, null, 2) + '\n', 'utf8');

console.log(`Wrote tokens.sd.json`);
