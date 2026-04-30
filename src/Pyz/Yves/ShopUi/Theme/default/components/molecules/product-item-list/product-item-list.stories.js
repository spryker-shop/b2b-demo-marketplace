import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, sectionFull } from 'storybook-helpers/docs';

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

export default { title: 'Molecules/Product Item List' };

export const Overview = {
    render: () => {
        return (
            sectionFull('Default list item',
                renderMolecule('product-item-list', {
                    data: {
                        product: {
                            name: 'Industrial Bolt Set',
                            url: '#',
                            sku: 'SKU-100',
                            abstractSku: 'AS-100',
                            images: [{ externalUrlSmall: 'https://placehold.co/200x200/e8e8e8/666?text=Product' }],
                            prices: { DEFAULT: 2499 },
                        },
                    },
                }),
            ) +
            sectionFull('With discount',
                renderMolecule('product-item-list', {
                    data: {
                        product: {
                            name: 'Ergonomic Monitor Arm',
                            url: '#',
                            sku: 'SKU-101',
                            abstractSku: 'AS-101',
                            images: [{ externalUrlSmall: 'https://placehold.co/200x200/e8e8e8/666?text=Arm' }],
                            prices: { DEFAULT: 5999, ORIGINAL: 7999 },
                        },
                    },
                }),
            )
        );
    },
};

export const API = {
    render: () => docs,
};
