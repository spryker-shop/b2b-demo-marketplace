import { mount } from 'ShopUi/app';
import Component from 'ShopUi/models/component';

export default class ProductCartItem extends Component {
    protected toggle: HTMLButtonElement | null = null;
    protected variantsMore: HTMLButtonElement | null = null;
    protected contextItems: HTMLElement[] = [];
    protected panelInner: HTMLElement | null = null;
    protected detailsRequest: Promise<void> | null = null;

    protected init(): void {
        this.toggle = this.querySelector<HTMLButtonElement>(`.${this.jsName}__toggle`);
        this.panelInner = this.querySelector<HTMLElement>(`.${this.jsName}__panel-inner`);

        this.toggle?.addEventListener('click', (event: Event) => {
            event.preventDefault();
            const isExpanded = this.toggle?.getAttribute('aria-expanded') === 'true';
            this.toggle?.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');

            if (!isExpanded) {
                void this.loadDetails();
            }
        });
        this.toggle?.addEventListener('pointerenter', () => void this.loadDetails());
        this.toggle?.addEventListener('focus', () => void this.loadDetails());

        this.variantsMore = this.querySelector<HTMLButtonElement>(`.${this.jsName}__variants-more`);
        this.variantsMore?.addEventListener('click', (event: Event) => {
            event.preventDefault();
            const isExpanded = this.variantsMore?.getAttribute('aria-expanded') === 'true';
            this.variantsMore?.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
        });

        this.contextItems = Array.from(this.querySelectorAll<HTMLElement>(`.${this.jsName}__context-item`));
        this.contextItems.forEach((item) => {
            item.addEventListener('click', (event: Event) => void this.onContextItemClick(event, item));
            item.addEventListener('pointerenter', () => void this.loadDetails());
            item.addEventListener('focus', () => void this.loadDetails());
        });
    }

    protected loadDetails(): Promise<void> {
        const url = this.panelInner?.getAttribute('data-details-url');

        if (!this.panelInner || !url) {
            return Promise.resolve();
        }

        if (this.detailsRequest) {
            return this.detailsRequest;
        }

        const panel = this.panelInner;

        panel.setAttribute('aria-busy', 'true');

        this.detailsRequest = fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((response: Response) => {
                if (!response.ok) {
                    throw new Error(`GET ${response.status} ${url}`);
                }

                return response.text();
            })
            .then(async (html: string) => {
                panel.innerHTML = html;
                panel.removeAttribute('data-details-url');

                // Injected markup contains components (cart-item-note, asset selector, ...) that need mounting.
                await mount();
            })
            .catch(() => {
                this.detailsRequest = null;
            })
            .finally(() => panel.removeAttribute('aria-busy'));

        return this.detailsRequest;
    }

    protected async onContextItemClick(event: Event, item: HTMLElement): Promise<void> {
        event.preventDefault();
        this.ensureExpanded();
        await this.loadDetails();

        const targetSelector = item.getAttribute('data-trigger-target');
        if (!targetSelector) {
            return;
        }

        const target = this.querySelector<HTMLElement>(targetSelector);
        if (!target) {
            return;
        }

        const focusSelector = item.getAttribute('data-focus-target');
        const field = focusSelector ? this.querySelector<HTMLElement>(focusSelector) : null;

        if (!field) {
            target.click();

            return;
        }

        if (!field.offsetParent) {
            target.click();
        }

        field.focus();
    }

    protected ensureExpanded(): void {
        if (this.toggle?.getAttribute('aria-expanded') !== 'true') {
            this.toggle?.click();
        }
    }
}
