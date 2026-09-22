import './filter-search.scss';
import register from 'ShopUi/app/registry';

export default register(
    'filter-search',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "filter-search" */
            './filter-search'
        ),
);
