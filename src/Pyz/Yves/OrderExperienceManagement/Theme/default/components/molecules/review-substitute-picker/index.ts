import './review-substitute-picker.scss';
import register from 'ShopUi/app/registry';

export default register(
    'review-substitute-picker',
    () =>
        import(
            /* webpackMode: "lazy", */
            /* webpackChunkName: "review-substitute-picker" */
            'OrderExperienceManagement/components/molecules/review-substitute-picker/review-substitute-picker'
        ),
);
