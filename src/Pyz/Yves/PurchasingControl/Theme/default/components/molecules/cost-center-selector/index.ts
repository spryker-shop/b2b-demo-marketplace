import './cost-center-selector.scss';
import register from 'ShopUi/app/registry';

export default register(
    'cost-center-selector',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "cost-center-selector" */
            './cost-center-selector'
        ),
);
