import './variant-configurator.scss';
import register from 'ShopUi/app/registry';

export default register(
    'variant-configurator',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "variant-configurator" */
            './variant-configurator'
        ),
);
