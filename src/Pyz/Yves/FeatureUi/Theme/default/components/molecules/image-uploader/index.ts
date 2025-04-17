import register from 'ShopUi/app/registry';
import './image-uploader.scss';
export default register(
    'image-uploader',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "image-uploader" */
            './image-uploader'
        ),
);
