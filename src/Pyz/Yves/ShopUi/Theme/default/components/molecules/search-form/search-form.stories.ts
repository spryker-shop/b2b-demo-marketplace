/* eslint-disable camelcase */
import { renderMolecule, renderTemplateById } from 'storybook-helpers/render-twig';
import { componentDocs, sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';

const docs = componentDocs({
    name: 'search-form',
    tag: 'div',
    extends: "molecule('search-form', '@SprykerShop:ShopUi')",
    data: [
        { prop: 'searchUrl', type: 'string', default: "''", desc: 'Form action URL for search' },
        { prop: 'searchValue', type: 'string', default: "''", desc: 'Pre-filled search input value' },
    ],
    modifiers: ['main-search', 'drawer'],
});

const meta: Meta = { title: 'Molecules/Search Form' };
export default meta;

const SUGGEST_URL = '/storybook-mock/search';

function buildSuggestionHtml() {
    return renderTemplateById('@CatalogPage/views/suggestion-results/suggestion-results.twig', {
        completion: [
            'hammerbacher',
            'sheet steel',
            'thermotech solutions',
            'steel sheets, stove-enameled',
            'ecoheat systems',
            'industrial thermal co',
        ],
        suggestionByType: {
            category: [
                { name: 'Heat Recovery Systems', url: '/DE/en/thermal-energy-systems/heat-recovery-systems' },
                {
                    name: 'Industrial Heat Pumps',
                    url: '/DE/en/air-conditioning-and-heating-solutions/industrial-heat-pumps',
                },
                {
                    name: 'Air Conditioning and Heating Solutions',
                    url: '/DE/en/air-conditioning-and-heating-solutions',
                },
            ],
            cms_page: [
                { name: 'Demo Landing Page', url: '/DE/en/demo-landing-page' },
                { name: 'GTC', url: '/DE/en/gtc' },
            ],
            product_abstract: [
                {
                    sku: 'HP-ECO-45K',
                    url: '/DE/en/highefficiencyheatpump45kw',
                    abstract_name: 'High-Efficiency Heat Pump 45kW',
                    images: [{ external_url_small: 'https://placehold.co/80x80/e8e8e8/666?text=Pump' }],
                    price: 19200,
                    prices: { ORIGINAL: 21000 },
                },
                {
                    sku: 'GS-HE-4002',
                    url: '/DE/en/gasketsetforheatexchanger',
                    abstract_name: 'Gasket Set for Heat Exchanger',
                    images: [{ external_url_small: 'https://placehold.co/80x80/e8e8e8/666?text=Gasket' }],
                    price: 5200,
                },
                {
                    sku: 'HPCP-3020',
                    url: '/DE/en/modularheatpumpcontrolpanel',
                    abstract_name: 'Modular Heat Pump Control Panel',
                    images: [{ external_url_small: 'https://placehold.co/80x80/e8e8e8/666?text=Panel' }],
                    price: 98000,
                },
                {
                    sku: 'HP-IND-60K',
                    url: '/DE/en/industrialheatpump60kw',
                    abstract_name: 'Industrial Heat Pump 60kW',
                    images: [{ external_url_small: 'https://placehold.co/80x80/e8e8e8/666?text=Pump' }],
                    price: 24700,
                },
                {
                    sku: 'M39658',
                    url: '/DE/en/stehpult-hoehenverstellbar-mobil-lichtgrau-M39658',
                    abstract_name: 'Standing desk, height-adjustable - mobile, light gray',
                    images: [{ external_url_small: 'https://placehold.co/80x80/e8e8e8/666?text=Desk' }],
                    price: 49900,
                },
                {
                    sku: 'M1013287',
                    url: '/DE/en/rednerpult-hoehenverstellbar-mobil-mit-4-rollen-M1013287',
                    abstract_name: 'Smit Visual lectern, height-adjustable - mobile, with 4 rollers',
                    images: [{ external_url_small: 'https://placehold.co/80x80/e8e8e8/666?text=Lectern' }],
                    price: 79000,
                },
            ],
        },
    });
}

// XHR mock reads the latest payload from this closure — render() rebuilds it
// each time after the storybook decorator has set translations.
let mockSuggestionsHtml = '';

let xhrPatched = false;
function installXhrMock() {
    if (xhrPatched) return;
    xhrPatched = true;

    const origOpen = window.XMLHttpRequest.prototype.open;
    const origSend = window.XMLHttpRequest.prototype.send;

    window.XMLHttpRequest.prototype.open = function (method, url, ...rest) {
        this.__mockMatch = typeof url === 'string' && url.includes('/storybook-mock/search/suggestion');
        return origOpen.call(this, method, url, ...rest);
    };

    window.XMLHttpRequest.prototype.send = function (body) {
        if (!this.__mockMatch) return origSend.call(this, body);

        Object.defineProperty(this, 'readyState', { value: 4, configurable: true });
        Object.defineProperty(this, 'status', { value: 200, configurable: true });
        Object.defineProperty(this, 'responseText', { value: mockSuggestionsHtml, configurable: true });
        Object.defineProperty(this, 'response', { value: mockSuggestionsHtml, configurable: true });

        setTimeout(() => {
            this.dispatchEvent(new Event('readystatechange'));
            this.dispatchEvent(new Event('load'));
            this.dispatchEvent(new Event('loadend'));
        }, 50);
    };
}

export const Overview: StoryObj = {
    translations: {
        'search.form.placeholder': 'Search products, e.g. "bolt"',
        'global.search': 'Search',
        'global.close': 'Close',
        'global.search.suggestions': 'Search suggestions',
        'global.search.suggestion.in_categories': 'Categories',
        'global.search.suggestion.in_cms': 'Pages',
        'global.search.suggestion.in_products': 'Products',
        'global.search.suggestion.see_all_products': 'See all products',
        'global.search.suggestion.no_result': 'No results found',
    },
    render: () => {
        installXhrMock();
        mockSuggestionsHtml = JSON.stringify({
            suggestion: buildSuggestionHtml(),
            completion: 'heavy duty industrial bolt set',
        });

        return sectionFull(
            'Search form with populated suggestions',
            `<div style="max-width: 600px; min-height: 720px; padding-top: 8px;">` +
                renderMolecule('search-form', {
                    data: { searchUrl: SUGGEST_URL },
                    modifiers: ['main-search'],
                }) +
                `</div>`,
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
