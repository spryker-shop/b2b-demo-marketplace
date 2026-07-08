import register from 'ShopUi/app/registry';
import './style.scss';
export default register(
    'image-uploader',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "image-uploader" */
            'SelfServicePortal/components/molecules/image-uploader/image-uploader'
        ),
);
