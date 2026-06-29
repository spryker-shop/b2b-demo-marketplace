import Component from 'ShopUi/models/component';

export default class ProductCartItem extends Component {
    protected toggle: HTMLButtonElement | null = null;
    protected variantsMore: HTMLButtonElement | null = null;
    protected contextItems: HTMLElement[] = [];

    protected readyCallback(): void {}

    protected init(): void {
        this.toggle = this.querySelector<HTMLButtonElement>(`.${this.jsName}__toggle`);
        this.toggle?.addEventListener('click', (event: Event) => {
            event.preventDefault();
            const isExpanded = this.toggle?.getAttribute('aria-expanded') === 'true';
            this.toggle?.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
        });

        this.variantsMore = this.querySelector<HTMLButtonElement>(`.${this.jsName}__variants-more`);
        this.variantsMore?.addEventListener('click', (event: Event) => {
            event.preventDefault();
            const isExpanded = this.variantsMore?.getAttribute('aria-expanded') === 'true';
            this.variantsMore?.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
        });

        this.contextItems = Array.from(
            this.querySelectorAll<HTMLElement>(`.${this.jsName}__context-item[data-trigger-target]`),
        );
        this.contextItems.forEach((item) => {
            item.addEventListener('click', (event: Event) => this.onContextItemClick(event, item));
        });
    }

    protected onContextItemClick(event: Event, item: HTMLElement): void {
        event.preventDefault();
        const targetSelector = item.getAttribute('data-trigger-target');
        if (!targetSelector) {
            return;
        }

        this.ensureExpanded();

        const target = this.querySelector<HTMLElement>(targetSelector);
        if (!target) {
            return;
        }

        target.click();
    }

    protected ensureExpanded(): void {
        if (this.toggle?.getAttribute('aria-expanded') !== 'true') {
            this.toggle?.click();
        }
    }
}
