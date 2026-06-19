import './volume-price.scss';
import register from 'ShopUi/app/registry';

export default register(
    'volume-price',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "volume-price" */
            './volume-price'
        ),
);
