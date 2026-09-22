import './review-scope-selector.scss';
import register from 'ShopUi/app/registry';
export default register(
    'review-scope-selector',
    () =>
        import(
            /* webpackMode: "eager" */
            /* webpackChunkName: "review-scope-selector" */
            'OrderExperienceManagement/components/molecules/review-scope-selector/review-scope-selector'
        ),
);
