import './flash-message.scss';
import register from 'ShopUi/app/registry';
export default register(
    'flash-message',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "flash-message" */
            './flash-message'
        ),
);
