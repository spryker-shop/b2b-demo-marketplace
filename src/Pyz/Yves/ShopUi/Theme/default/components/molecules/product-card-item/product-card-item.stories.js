import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, sectionFull } from 'storybook-helpers/docs';

const docs = componentDocs({
    name: 'product-card-item',
    tag: 'article',
    extends: "model('component')",
    data: [
        { prop: 'listItem', type: 'object', default: 'required', desc: 'Product item object (name, sku, url, quantity, unitPrice, images, etc.)' },
        { prop: 'list', type: 'object', default: '{}', desc: 'Parent list / quote object' },
        { prop: 'attributes', type: 'array', default: '[]', desc: 'Product attributes for variant selector' },
        { prop: 'options', type: 'array', default: '[]', desc: 'Product option values' },
        { prop: 'canEdit', type: 'boolean', default: 'false', desc: 'Allow editing (quantity, configuration)' },
        { prop: 'canRemove', type: 'boolean', default: 'false', desc: 'Show remove/delete button' },
        { prop: 'quantity', type: 'int|null', default: 'null', desc: 'Explicit quantity override' },
        { prop: 'imageModifiers', type: 'array', default: '[]', desc: 'Modifiers for the product-item-image molecule' },
        { prop: 'priceModifiers', type: 'array', default: "['medium']", desc: 'Modifiers for the money-price molecule' },
        { prop: 'isShoppingList', type: 'boolean', default: 'false', desc: 'Shopping list context flag' },
        { prop: 'currencyIsoCode', type: 'string|null', default: 'null', desc: 'Currency ISO code for price display' },
        { prop: 'ajaxTriggerAttribute', type: 'string|null', default: 'null', desc: 'Ajax trigger data attribute name' },
    ],
    modifiers: ['checkout', 'checkout-address', 'order-detail', 'shopping-list-alternative', 'shopping-list-available'],
});

export default { title: 'Molecules/Product Card Item' };

export const Overview = {
    translations: {
        'cart.item.sku': 'SKU',
        'cart.item_quantity': 'Qty',
        'cart.delete.item': 'Remove'
    },
    render: () => {

        return (
            sectionFull('Editable item',
                renderMolecule('product-card-item', {
                    data: {
                        listItem: {
                            name: 'Office Chair Pro',
                            sku: 'SKU-001',
                            url: '#',
                            quantity: 2,
                            unitPrice: 29900,
                            price: 29900,
                            images: [{ externalUrlLarge: 'https://placehold.co/150x150/e8e8e8/666?text=Product' }],
                        },
                        canEdit: true,
                        canRemove: true,
                    },
                }),
            ) +
            sectionFull('Read-only item',
                renderMolecule('product-card-item', {
                    data: {
                        listItem: {
                            name: 'Standing Desk',
                            sku: 'SKU-002',
                            url: '#',
                            quantity: 1,
                            unitPrice: 49900,
                            price: 49900,
                            images: [{ externalUrlLarge: 'https://placehold.co/150x150/e8e8e8/666?text=Desk' }],
                        },
                        canEdit: false,
                        canRemove: false,
                    },
                }),
            )
        );
    },
};

export const API = {
    render: () => docs,
};
