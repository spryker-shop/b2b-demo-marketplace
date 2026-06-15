import Component from 'ShopUi/models/component';

export default class SellerListItem extends Component {
    protected menu: HTMLDetailsElement;

    protected readyCallback(): void {}

    protected init(): void {
        this.menu = <HTMLDetailsElement>this.getElementsByClassName(`${this.jsName}__menu`)[0];

        if (!this.menu) {
            return;
        }

        document.addEventListener('click', (event: Event) => this.onDocumentClick(event));
    }

    protected onDocumentClick(event: Event): void {
        if (this.menu.open && !this.menu.contains(<Node>event.target)) {
            this.menu.open = false;
        }
    }
}
