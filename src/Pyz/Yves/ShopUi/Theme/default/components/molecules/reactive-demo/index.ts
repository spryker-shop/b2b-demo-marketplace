import './reactive-demo.scss';
import register from 'ShopUi/app/registry';
export default register(
    'reactive-demo',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "reactive-demo" */
            './reactive-demo'
        ),
);
