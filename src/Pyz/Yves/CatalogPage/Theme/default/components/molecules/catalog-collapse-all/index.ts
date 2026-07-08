import './style.scss';
import register from 'ShopUi/app/registry';

export default register(
    'catalog-collapse-all',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "catalog-collapse-all" */
            './catalog-collapse-all'
        ),
);
