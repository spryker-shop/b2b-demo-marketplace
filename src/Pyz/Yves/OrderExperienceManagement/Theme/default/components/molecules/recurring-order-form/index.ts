import './style.scss';
import register from 'ShopUi/app/registry';

export default register(
    'recurring-order-form',
    () =>
        import(
            /* webpackMode: "lazy", */
            /* webpackChunkName: "recurring-order-form" */
            './recurring-order-form'
        ),
);
