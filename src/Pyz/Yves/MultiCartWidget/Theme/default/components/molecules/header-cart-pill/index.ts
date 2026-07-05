import './style.scss';
import register from 'ShopUi/app/registry';
export default register(
    'header-cart-pill',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "header-cart-pill" */
            './header-cart-pill'
        ),
);
