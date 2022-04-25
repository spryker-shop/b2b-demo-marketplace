import './merchant-product-offer-dynamic-form-elements.scss';
import register from 'ShopUi/app/registry';
export default register(
    'merchant-product-offer-dynamic-form-elements',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "merchant-product-offer-dynamic-form-elements" */
            'MerchantProductOfferWidget/components/molecules/merchant-product-offer-dynamic-form-elements/merchant-product-offer-dynamic-form-elements'
        ),
);
