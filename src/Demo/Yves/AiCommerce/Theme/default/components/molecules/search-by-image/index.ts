import './search-by-image';
import register from 'ShopUi/app/registry';

export default register(
    'search-by-image',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "search-by-image" */
            'AiCommerce/components/molecules/search-by-image/search-by-image'
        ),
);
