import type Twig from 'twig';

function escapeHtml(value: string): string {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// twig.js hands a Twig array to a function as a JS array and a Twig hash as a plain object whose
// `_keys` property records the declaration order of its keys.
const TWIG_HASH_KEY_ORDER = '_keys';

function isTwigHash(value: unknown): value is Record<string, unknown> {
    return Boolean(value) && typeof value === 'object' && !Array.isArray(value);
}

function hashKeys(hash: Record<string, unknown>): string[] {
    const declaredOrder = hash[TWIG_HASH_KEY_ORDER];
    if (Array.isArray(declaredOrder)) return declaredOrder.map(String);
    return Object.keys(hash).filter((key) => key !== TWIG_HASH_KEY_ORDER);
}

function toIterable(value: unknown): unknown[] {
    if (Array.isArray(value)) return value;
    if (isTwigHash(value)) return hashKeys(value).map((key) => value[key]);
    return [];
}

function toEntries(value: unknown): Array<[string, unknown]> {
    if (Array.isArray(value)) return value.map((item, index) => [String(index), item]);
    if (isTwigHash(value)) return hashKeys(value).map((key) => [key, value[key]]);
    return [];
}

// `models/component.twig` calls these PHP-side Twig functions directly (ShopUiTwigExtension
// `renderComponentClass` / `renderComponentAttributes`); the output must match byte for byte so
// component markup in Storybook is the markup Yves renders.
export function registerComponentFunctions(twig: typeof Twig): void {
    twig.extendFunction('componentClass', (componentName: unknown, modifiers?: unknown, extraClass?: unknown) => {
        const name = String(componentName ?? '');
        let renderedClass = escapeHtml(name.trim());

        for (const modifier of toIterable(modifiers)) {
            const trimmedModifier = String(modifier ?? '').trim();
            if (trimmedModifier === '') continue;
            renderedClass += ` ${escapeHtml(name)}--${escapeHtml(trimmedModifier)}`;
        }

        if (extraClass) {
            renderedClass += ` ${escapeHtml(String(extraClass))}`;
        }

        return renderedClass;
    });

    twig.extendFunction('componentAttributes', (attributes?: unknown) => {
        let renderedAttributes = '';

        for (const [name, value] of toEntries(attributes)) {
            if (value === true) {
                renderedAttributes += ` ${escapeHtml(name)}`;
                continue;
            }
            if (value === false || value === null || value === undefined) continue;
            renderedAttributes += ` ${escapeHtml(name)}='${escapeHtml(String(value))}'`;
        }

        return renderedAttributes;
    });
}
