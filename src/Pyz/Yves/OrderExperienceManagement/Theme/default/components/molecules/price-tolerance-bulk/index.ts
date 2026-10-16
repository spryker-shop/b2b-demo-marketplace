import './price-tolerance-bulk.scss';
import register from 'ShopUi/app/registry';

export default register(
    'price-tolerance-bulk',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "price-tolerance-bulk" */
            'OrderExperienceManagement/components/molecules/price-tolerance-bulk/price-tolerance-bulk'
        ),
);
