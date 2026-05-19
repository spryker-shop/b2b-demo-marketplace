import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, figmaLink, sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';

const FIGMA_URL = 'https://www.figma.com/design/6kdOU2Ez2KVChX26VSMPe7/Demo-DS-master?node-id=476-7860&p=f&m=dev';

const docs = componentDocs({
    name: 'product-item-list',
    tag: 'product-item-list',
    extends: "molecule('product-item-list', '@SprykerShop:ShopUi')",
    data: [
        { prop: 'name', type: 'string', default: "''", desc: 'Product name' },
        { prop: 'url', type: 'string', default: "''", desc: 'Product detail page URL' },
        { prop: 'image', type: 'string', default: "''", desc: 'Product image URL' },
        {
            prop: 'product',
            type: 'object',
            default: '{}',
            desc: 'Full product transfer object (name, sku, prices, images, etc.)',
        },
    ],
    modifiers: ['order'],
});

const meta: Meta = { title: 'Molecules/Product Item List' };
export default meta;

export const Overview: StoryObj = {
    translations: {
        'product.view': 'View Product',
        'price.mode.incl_vat': 'incl. VAT',
        'price.mode.excl_vat': 'excl. VAT',
        'product.color-selector': 'Available Colors',
    },
    render: () => {
        return (
            figmaLink(FIGMA_URL) +
            sectionFull(
                'Product Item List',
                renderMolecule('product-item-list', {
                data: {
                    product: {
                        name: 'Ergonomic Monitor Arm',
                        url: '#',
                        sku: 'SKU-101',
                        abstractSku: 'AS-101',
                        idProductAbstract: 101,
                        images: [{ externalUrlSmall: 'https://placehold.co/200x200/e8e8e8/666?text=Arm' }],
                        prices: { DEFAULT: 5999, ORIGINAL: 7999 },
                    },
                },
                widgets: {
                    DisplayProductAbstractReviewWidget: {
                        productReviewStorageTransfer: { averageRating: 4.0, reviewCount: 8 },
                        maximumRating: 5,
                    },
                    ProductAbstractLabelWidget: {
                        productLabelDictionaryItemTransfers: [
                            { name: 'New', frontEndReference: 'success', key: 'new' },
                            { name: 'Sale', frontEndReference: 'sale', key: 'sale' },
                        ],
                    },
                    ProductGroupColorWidget: {
                        productGroupItems: [
                            {
                                colorCode: '#1f1f1f',
                                name: 'Ergonomic Monitor Arm — Black',
                                sku: 'AS-101-blk',
                                abstractSku: 'AS-101',
                                url: '#',
                                available: true,
                                idProductConcrete: 1,
                                attributes: { color: 'Black', farbe: 'black' },
                                images: [
                                    {
                                        externalUrlSmall: 'https://placehold.co/200x200/1f1f1f/fff?text=Black',
                                        altText: 'Black variant',
                                    },
                                ],
                                labels: [
                                    { key: 'NEW', frontEndReference: 'success' },
                                    { key: 'BESTSELLER', frontEndReference: 'info' },
                                    { key: 'SALE', frontEndReference: 'sale' },
                                ],
                                rating: { averageRating: 4.5 },
                                prices: { DEFAULT: 5999, ORIGINAL: 7999 },
                            },
                            {
                                colorCode: '#c62828',
                                name: 'Ergonomic Monitor Arm — Red',
                                sku: 'AS-101-red',
                                abstractSku: 'AS-101',
                                url: '#',
                                available: true,
                                idProductConcrete: 2,
                                attributes: { color: 'Red', farbe: 'red' },
                                images: [
                                    {
                                        externalUrlSmall: 'https://placehold.co/200x200/c62828/fff?text=Red',
                                        altText: 'Red variant',
                                    },
                                ],
                                labels: [
                                    { key: 'TOP', frontEndReference: 'info' },
                                    { key: 'SALE', frontEndReference: 'sale' },
                                ],
                                rating: { averageRating: 4.0 },
                                prices: { DEFAULT: 5999, ORIGINAL: 7999 },
                            },
                            {
                                colorCode: '#1565c0',
                                name: 'Ergonomic Monitor Arm — Blue',
                                sku: 'AS-101-blu',
                                abstractSku: 'AS-101',
                                url: '#',
                                available: true,
                                idProductConcrete: 3,
                                attributes: { color: 'Blue', farbe: 'blue' },
                                images: [
                                    {
                                        externalUrlSmall: 'https://placehold.co/200x200/1565c0/fff?text=Blue',
                                        altText: 'Blue variant',
                                    },
                                ],
                                labels: [{ key: 'BESTSELLER', frontEndReference: 'info' }],
                                rating: { averageRating: 5.0 },
                                prices: { DEFAULT: 5999 },
                            },
                        ],
                    },
                },
            }),
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
