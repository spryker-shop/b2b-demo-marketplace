import './volume-price-table.scss';
import register from 'ShopUi/app/registry';

export default register(
    'volume-price-table',
    () =>
        import(
            /* webpackMode: "eager" */
            /* webpackChunkName: "volume-price-table" */
            './volume-price-table'
        ),
);
