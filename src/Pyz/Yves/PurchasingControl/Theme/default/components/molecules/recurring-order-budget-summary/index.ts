import './recurring-order-budget-summary.scss';
import register from 'ShopUi/app/registry';
export default register(
    'recurring-order-budget-summary',
    () =>
        import(
            /* webpackMode: "eager" */
            /* webpackChunkName: "recurring-order-budget-summary" */
            './recurring-order-budget-summary'
        ),
);
