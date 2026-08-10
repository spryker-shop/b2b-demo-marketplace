import './review-substitute.scss';
import register from 'ShopUi/app/registry';

export default register(
    'review-substitute',
    () =>
        import(
            /* webpackMode: "lazy", */
            /* webpackChunkName: "review-substitute" */
            'OrderExperienceManagement/components/molecules/review-substitute/review-substitute'
        ),
);
