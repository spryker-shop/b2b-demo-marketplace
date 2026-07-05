import './style.scss';
import register from 'ShopUi/app/registry';
export default register(
    'mini-cart-detail',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "mini-cart-detail" */
            './mini-cart-detail'
        ),
);
