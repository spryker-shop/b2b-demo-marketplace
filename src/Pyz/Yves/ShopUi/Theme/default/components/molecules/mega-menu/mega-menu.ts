import Component from 'ShopUi/models/component';

export default class MegaMenu extends Component {
    protected sidebarItems: HTMLElement[];

    protected readyCallback(): void {}

    protected init(): void {
        this.sidebarItems = Array.from(this.querySelectorAll<HTMLElement>(`.${this.name}__sidebar-item`));

        if (this.sidebarItems.length) {
            this.sidebarItems[0].classList.add('is-active');
        }

        this.sidebarItems.forEach((item) => {
            const activate = () => {
                this.sidebarItems.forEach((i) => i.classList.remove('is-active'));
                item.classList.add('is-active');
            };

            item.addEventListener('mouseenter', activate);
            item.addEventListener('focusin', activate);
        });
    }
}
