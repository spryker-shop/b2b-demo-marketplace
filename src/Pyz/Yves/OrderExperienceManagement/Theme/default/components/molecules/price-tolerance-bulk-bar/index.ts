import './price-tolerance-bulk-bar.scss';
import register from 'ShopUi/app/registry';

export default register(
    'price-tolerance-bulk-bar',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "price-tolerance-bulk-bar" */
            './price-tolerance-bulk-bar'
        ),
);
