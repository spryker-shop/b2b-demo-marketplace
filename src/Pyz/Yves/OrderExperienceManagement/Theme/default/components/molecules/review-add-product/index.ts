import './review-add-product.scss';
import register from 'ShopUi/app/registry';

export default register(
    'review-add-product',
    () =>
        import(
            /* webpackMode: "lazy", */
            /* webpackChunkName: "review-add-product" */
            './review-add-product'
        ),
);
