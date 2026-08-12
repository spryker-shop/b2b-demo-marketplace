import './review-add-product-picker.scss';
import register from 'ShopUi/app/registry';

export default register(
    'review-add-product-picker',
    () =>
        import(
            /* webpackMode: "lazy", */
            /* webpackChunkName: "review-add-product-picker" */
            './review-add-product-picker'
        ),
);
