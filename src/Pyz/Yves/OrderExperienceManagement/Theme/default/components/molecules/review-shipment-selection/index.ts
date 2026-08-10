import 'OrderExperienceManagement/components/molecules/review-shipment-selection/style.scss';
import register from 'ShopUi/app/registry';

export default register(
    'review-shipment-selection',
    () =>
        import(
            /* webpackMode: "lazy", */
            /* webpackChunkName: "review-shipment-selection" */
            'OrderExperienceManagement/components/molecules/review-shipment-selection/review-shipment-selection'
        ),
);
