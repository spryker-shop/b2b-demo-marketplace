import './style.scss';
import register from 'ShopUi/app/registry';
export default register(
    'agent-control',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "agent-control" */
            './agent-control'
        ),
);
