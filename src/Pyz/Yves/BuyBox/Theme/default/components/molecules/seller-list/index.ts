import './style.scss';
import register from 'ShopUi/app/registry';

export default register(
    'seller-list',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "seller-list" */
            './seller-list'
        ),
);
