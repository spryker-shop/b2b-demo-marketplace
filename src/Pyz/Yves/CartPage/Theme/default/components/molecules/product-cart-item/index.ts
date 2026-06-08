import './product-cart-item.scss';
import register from 'ShopUi/app/registry';

export default register(
    'product-cart-item',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "product-cart-item" */
            './product-cart-item'
        ),
);
