import './recurring-order-selector.scss';
import register from 'ShopUi/app/registry';

export default register(
    'recurring-order-selector',
    () =>
        import(
            /* webpackMode: "lazy", */
            /* webpackChunkName: "recurring-order-selector" */
            'OrderExperienceManagement/components/molecules/recurring-order-selector/recurring-order-selector'
        ),
);
