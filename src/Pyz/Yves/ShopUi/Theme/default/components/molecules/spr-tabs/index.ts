import './style.scss';
import register from 'ShopUi/app/registry';
export default register(
    'spr-tabs',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "spr-tabs" */
            './spr-tabs'
        ),
);
