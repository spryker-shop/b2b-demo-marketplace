import register from 'ShopUi/app/registry';
import './style.scss';

export default register(
    'save-to-shopping-list',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "save-to-shopping-list" */
            './save-to-shopping-list'
        ),
);
