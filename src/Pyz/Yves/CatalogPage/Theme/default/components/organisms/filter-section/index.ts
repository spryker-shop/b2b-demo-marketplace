import './filter-section.scss';
import register from 'ShopUi/app/registry';

export default register(
    'filter-section',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "filter-section" */
            './filter-section'
        ),
);
