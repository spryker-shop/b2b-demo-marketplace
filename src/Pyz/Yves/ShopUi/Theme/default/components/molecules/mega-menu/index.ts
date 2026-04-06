import './mega-menu.scss';
import register from 'ShopUi/app/registry';
export default register(
    'mega-menu',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "mega-menu" */
            './mega-menu'
        ),
);
