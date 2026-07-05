import './style.scss';
import register from 'ShopUi/app/registry';
export default register(
    'product-detail-color-selector',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "product-detail-color-selector" */
            './product-detail-color-selector'
        ),
);
