import './cost-price.scss';
import register from 'ShopUi/app/registry';
export default register(
    'cost-price',
    () =>
        import(
            /* webpackMode: "eager" */
            /* webpackChunkName: "cost-price" */
            './cost-price'
        ),
);
