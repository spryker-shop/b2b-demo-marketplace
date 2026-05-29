import register from 'ShopUi/app/registry';
export default register(
    'cost-center-budget-filter',
    () =>
        import(
            /* webpackMode: "eager" */
            /* webpackChunkName: "cost-center-budget-filter" */
            './cost-center-budget-filter'
        ),
);
