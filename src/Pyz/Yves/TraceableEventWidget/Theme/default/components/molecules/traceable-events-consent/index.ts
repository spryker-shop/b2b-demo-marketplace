import register from 'ShopUi/app/registry';
export default register(
    'traceable-events-consent',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "traceable-events-consent" */
            './traceable-events-consent'
        ),
);
