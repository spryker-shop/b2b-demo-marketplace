import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';


const docs = componentDocs({
    name: 'product-item-list',
    tag: 'product-item-list',
    extends: "molecule('product-item-list', '@SprykerShop:ShopUi')",
    data: [
        { prop: 'name', type: 'string', default: "''", desc: 'Product name' },
        { prop: 'url', type: 'string', default: "''", desc: 'Product detail page URL' },
        { prop: 'image', type: 'string', default: "''", desc: 'Product image URL' },
        { prop: 'product', type: 'object', default: '{}', desc: 'Full product transfer object (name, sku, prices, images, etc.)' },
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
    },
    render: () => {
        return (
            sectionFull('Product Item List',
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
                    },
                }),
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
