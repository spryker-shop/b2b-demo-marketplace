import register from 'ShopUi/app/registry';
export default register(
    'reactive-bind',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "reactive-bind" */
            './reactive-bind'
        ),
);
