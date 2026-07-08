import './style.scss';
import register from 'ShopUi/app/registry';
export default register(
    'product-search-autocomplete-form',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "product-search-autocomplete-form" */
            './product-search-autocomplete-form'
        ),
);
