import './seller-list-item.scss';
import register from 'ShopUi/app/registry';

export default register(
    'seller-list-item',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "seller-list-item" */
            './seller-list-item'
        ),
);
