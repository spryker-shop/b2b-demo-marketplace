import Component from 'ShopUi/models/component';

export default class VariantConfigurator extends Component {
    protected cartForm: HTMLFormElement;
    protected notify: HTMLElement;

    protected readyCallback(): void {}

    protected init(): void {
        this.cartForm = <HTMLFormElement>document.querySelector(this.cartFormSelector);
        this.notify = <HTMLElement>this.getElementsByClassName(`${this.jsName}__notify`)[0];

        this.cartForm?.addEventListener('submit', (event: Event) => this.onCartFormSubmit(event));
    }

    protected onCartFormSubmit(event: Event): void {
        const emptySelects = this.emptyVariantSelects;

        if (!emptySelects.length) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        this.highlightEmptyVariants(emptySelects);
        this.notify?.classList.remove(this.toggleClassName);
    }

    protected highlightEmptyVariants(selects: HTMLSelectElement[]): void {
        selects.forEach((select) => select.classList.add(this.errorClassName));
    }

    protected get emptyVariantSelects(): HTMLSelectElement[] {
        return Array.from(this.getElementsByTagName('select')).filter((select) => !select.value);
    }

    protected get cartFormSelector(): string {
        return this.getAttribute('cart-form-selector') || '.js-product-configurator__form-add-to-cart';
    }

    protected get errorClassName(): string {
        return this.getAttribute('error-class-name') || 'custom-select__select--error';
    }

    protected get toggleClassName(): string {
        return this.getAttribute('toggle-class-name') || 'is-hidden';
    }
}
