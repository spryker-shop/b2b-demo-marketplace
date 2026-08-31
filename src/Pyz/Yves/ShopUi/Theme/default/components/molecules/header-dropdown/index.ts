import './header-dropdown.scss';
import register from 'ShopUi/app/registry';
export default register(
    'header-dropdown',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "header-dropdown" */
            './header-dropdown'
        ),
);
