import './style.scss';
import register from 'ShopUi/app/registry';
export default register(
    'toggler-radio',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "toggler-radio" */
            './toggler-radio'
        ),
);
