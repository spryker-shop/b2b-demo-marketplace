import './product-cart-item.scss';
import register from 'ShopUi/app/registry';

export default register(
    'cart-item-details',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "cart-item-details" */
            './cart-item-details'
        ),
);
