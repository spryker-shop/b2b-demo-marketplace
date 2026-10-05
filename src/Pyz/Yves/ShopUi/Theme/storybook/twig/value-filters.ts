import type Twig from 'twig';

const CENTS_PER_CURRENCY_UNIT = 100;
const MONEY_FRACTION_DIGITS = 2;

// `trans` is registered in engine.ts because it reads the translation state owned there.
export function registerValueFilters(twig: typeof Twig): void {
    twig.extendFilter('money', (v: number | string | null | undefined) => {
        if (v == null) return '';
        const num = typeof v === 'number' ? v / CENTS_PER_CURRENCY_UNIT : parseFloat(String(v));
        return isNaN(num) ? v : `€${num.toFixed(MONEY_FRACTION_DIGITS)}`;
    });
    twig.extendFilter('moneyRaw', (v: number | string | null | undefined) => {
        if (v == null) return '';
        const num = typeof v === 'number' ? v / CENTS_PER_CURRENCY_UNIT : parseFloat(String(v));
        return isNaN(num) ? v : num.toFixed(MONEY_FRACTION_DIGITS);
    });
    twig.extendFilter('trimLocale', (v: unknown) => v);
    twig.extendFilter('executeFilterIfExists', (v: unknown) => v);
    twig.extendFilter('raw', (v: unknown) => v);
    twig.extendFilter('sb_map_first', (value: unknown) =>
        Array.isArray(value)
            ? value.map((item: unknown) => (typeof item === 'string' && item.length ? item[0] : ''))
            : value,
    );
}
