import './header-shopping-list-pill.scss';
import register from 'ShopUi/app/registry';
export default register(
    'header-shopping-list-pill',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "header-shopping-list-pill" */
            './header-shopping-list-pill'
        ),
);
