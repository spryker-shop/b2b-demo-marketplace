import './style.scss';
import register from 'ShopUi/app/registry';
export default register(
    'date-time-picker',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "date-time-picker" */
            './date-time-picker'
        ),
);
