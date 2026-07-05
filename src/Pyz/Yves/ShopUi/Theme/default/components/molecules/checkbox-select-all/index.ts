import './style.scss';
import register from 'ShopUi/app/registry';
export default register('checkbox-select-all', () => import(/* webpackMode: "eager" */ './checkbox-select-all'));
