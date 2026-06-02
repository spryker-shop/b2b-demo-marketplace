import Component from 'ShopUi/models/component';

export default class ProductCartItem extends Component {
    protected toggle: HTMLButtonElement | null;

    protected readyCallback(): void {}

    protected init(): void {
        this.toggle = this.querySelector<HTMLButtonElement>('.product-cart-item__toggle');
        this.toggle?.addEventListener('click', (event: Event) => {
            event.preventDefault();
            const isExpanded = this.toggle?.getAttribute('aria-expanded') === 'true';
            this.toggle?.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
        });
    }
}
