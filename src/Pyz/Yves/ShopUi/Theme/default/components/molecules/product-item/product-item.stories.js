import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, sectionFull } from 'storybook-helpers/docs';

const docs = componentDocs({
    name: 'product-item',
    tag: 'div',
    extends: "molecule('product-item', '@SprykerShop:ShopUi')",
    data: [
        { prop: 'name', type: 'string', default: "''", desc: 'Product name' },
        { prop: 'url', type: 'string', default: "''", desc: 'Product detail page URL' },
        { prop: 'image', type: 'string', default: "''", desc: 'Product image URL' },
        { prop: 'price', type: 'int', default: "''", desc: 'Default price in cents' },
        { prop: 'category', type: 'string', default: "''", desc: 'Product category label' },
    ],
    modifiers: ['equal-height', 'full-height'],
});

export default { title: 'Molecules/Product Item' };

export const Overview = {
    translations: {
        'product.view': 'View',
        'price.mode.incl_vat': 'incl. VAT',
        'price.mode.excl_vat': 'excl. VAT',
    },
    render: () => {
        const wrap = (html) => `<div style="max-width: 300px;">${html}</div>`;

        return (
            sectionFull('Product item with review and labels',
                wrap(renderMolecule('product-item', {
                    data: {
                        category: 'Power Tools',
                        product: {
                            name: 'Cordless Impact Driver 18V',
                            url: '#',
                            sku: 'SKU-003',
                            abstractSku: 'AS-003',
                            idProductAbstract: 1003,
                            images: [{ externalUrlSmall: 'https://placehold.co/200x200/e8e8e8/666?text=Tool' }],
                            prices: { DEFAULT: 3499, ORIGINAL: 4999 },
                        },
                    },
                    widgets: {
                        DisplayProductAbstractReviewWidget: {
                            productReviewStorageTransfer: {
                                averageRating: 4.5,
                                reviewCount: 24,
                            },
                            maximumRating: 5,
                        },
                        ProductAbstractLabelWidget: {
                            productLabelDictionaryItemTransfers: [
                                { name: 'New', frontEndReference: 'success', key: 'new' },
                                { name: '-20%', frontEndReference: 'sale', key: 'sale' },
                            ],
                        },
                        // ProductGroupColorWidget renders when productGroupItems
                        // has > 1 entry. Each item must populate every field
                        // that color-selector.twig drops onto the button as a
                        // `data-product-*` attribute (image, name, labels,
                        // rating, prices). On `mouseenter` / `focusin` the
                        // color-selector reads them and pushes them into the
                        // parent product-item — that's how the live shop
                        // swaps image, title and label group when you hover
                        // a color swatch.
                        ProductGroupColorWidget: {
                            productGroupItems: [
                                {
                                    colorCode: '#1f1f1f',
                                    name: 'Cordless Impact Driver 18V — Black',
                                    sku: 'AS-003-blk',
                                    abstractSku: 'AS-003',
                                    url: '#',
                                    available: true,
                                    idProductConcrete: 1,
                                    attributes: { color: 'Black' },
                                    images: [{ externalUrlSmall: 'https://placehold.co/200x200/1f1f1f/fff?text=Black', altText: 'Black variant' }],
                                    labels: [{ key: 'NEW', frontEndReference: 'success' }],
                                    rating: { averageRating: 4.5 },
                                    prices: { DEFAULT: 3499, ORIGINAL: 4999 },
                                },
                                {
                                    colorCode: '#c62828',
                                    name: 'Cordless Impact Driver 18V — Red',
                                    sku: 'AS-003-red',
                                    abstractSku: 'AS-003',
                                    url: '#',
                                    available: true,
                                    idProductConcrete: 2,
                                    attributes: { color: 'Red' },
                                    images: [{ externalUrlSmall: 'https://placehold.co/200x200/c62828/fff?text=Red', altText: 'Red variant' }],
                                    labels: [
                                        { key: 'TOP', frontEndReference: 'info' },
                                        { key: 'SALE', frontEndReference: 'sale' },
                                    ],
                                    rating: { averageRating: 4.0 },
                                    prices: { DEFAULT: 3499, ORIGINAL: 4999 },
                                },
                                {
                                    colorCode: '#1565c0',
                                    name: 'Cordless Impact Driver 18V — Blue',
                                    sku: 'AS-003-blu',
                                    abstractSku: 'AS-003',
                                    url: '#',
                                    available: true,
                                    idProductConcrete: 3,
                                    attributes: { color: 'Blue' },
                                    images: [{ externalUrlSmall: 'https://placehold.co/200x200/1565c0/fff?text=Blue', altText: 'Blue variant' }],
                                    labels: [],
                                    rating: { averageRating: 5.0 },
                                    prices: { DEFAULT: 3499 },
                                },
                            ],
                        },
                    },
                })),
            )
        );
    },
};

export const API = {
    render: () => docs,
};
