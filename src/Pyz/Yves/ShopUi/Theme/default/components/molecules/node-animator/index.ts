import './style.scss';
import register from 'ShopUi/app/registry';
export default register(
    'node-animator',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "node-animator" */
            './node-animator'
        ),
);
