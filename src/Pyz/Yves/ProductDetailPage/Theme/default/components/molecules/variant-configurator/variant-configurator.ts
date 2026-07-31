import Component from 'ShopUi/models/component';

export default class VariantConfigurator extends Component {
    protected notify: HTMLElement;
    protected shoppingListNotify: HTMLElement;

    protected readyCallback(): void {}

    protected init(): void {
        this.notify = <HTMLElement>this.getElementsByClassName(`${this.jsName}__notify`)[0];
        this.shoppingListNotify = <HTMLElement>this.getElementsByClassName(`${this.jsName}__notify-shopping-list`)[0];

        document.addEventListener('submit', (event: Event) => this.onCartFormSubmit(event), true);
        document.addEventListener('click', (event: Event) => this.onSaveToShoppingListClick(event), true);
    }

    protected onCartFormSubmit(event: Event): void {
        if (!(<HTMLElement>event.target).closest(this.cartFormSelector)) {
            return;
        }

        const emptySelects = this.emptyVariantSelects;

        if (!emptySelects.length) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        this.highlightEmptyVariants(emptySelects);
        this.showNotify(this.notify);
    }

    protected onSaveToShoppingListClick(event: Event): void {
        if (!(<HTMLElement>event.target).closest(`.${this.saveToShoppingListTriggerClassName}`)) {
            return;
        }

        const emptySelects = this.emptyVariantSelects;

        if (!emptySelects.length) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        this.highlightEmptyVariants(emptySelects);
        this.showNotify(this.shoppingListNotify);
    }

    protected highlightEmptyVariants(selects: HTMLSelectElement[]): void {
        selects.forEach((select) => select.classList.add(this.errorClassName));
    }

    protected showNotify(activeNotify: HTMLElement): void {
        [this.notify, this.shoppingListNotify].forEach((notifyElement) =>
            notifyElement?.classList.add(this.toggleClassName),
        );
        activeNotify?.classList.remove(this.toggleClassName);
    }

    protected get emptyVariantSelects(): HTMLSelectElement[] {
        return Array.from(this.getElementsByTagName('select')).filter((select) => !select.value);
    }

    protected get cartFormSelector(): string {
        return this.getAttribute('cart-form-selector') || '.js-product-configurator__form-add-to-cart';
    }

    protected get saveToShoppingListTriggerClassName(): string {
        return this.getAttribute('save-to-shopping-list-trigger-class-name') || 'js-save-to-shopping-list__trigger';
    }

    protected get errorClassName(): string {
        return this.getAttribute('error-class-name') || 'custom-select__select--error';
    }

    protected get toggleClassName(): string {
        return this.getAttribute('toggle-class-name') || 'is-hidden';
    }
}
