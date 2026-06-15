import QuickOrderRowCore from 'QuickOrderPage/components/molecules/quick-order-row/quick-order-row';
import AutocompleteForm from 'ShopUi/components/molecules/autocomplete-form/autocomplete-form';
import AjaxProvider from 'ShopUi/components/molecules/ajax-provider/ajax-provider';

export default class QuickOrderRow extends QuickOrderRowCore {
    protected init(): void {
        this.ajaxProvider = <AjaxProvider>this.getElementsByClassName(`${this.jsName}__provider`)[0];
        this.autocompleteInput = <AutocompleteForm>this.getElementsByClassName(this.autocompleteFormClassName)[0];

        this.registerQuantityInput();
        this.registerAdditionalFormElements();
        this.mapEvents();
    }

    protected mapQuantityInputChange(): void {
        this.quantityInput.addEventListener('change', () => {
            queueMicrotask(() => this.reloadField(this.autocompleteInput.inputValue));
        });
    }

    protected mapAdditionalFormElementChange(): void {
        if (!this.additionalFormElements || !this.additionalFormElements.length) {
            return;
        }

        this.additionalFormElements.forEach((item) => {
            item.addEventListener('change', () => this.reloadField(this.autocompleteInput.inputValue));
        });
    }

    async reloadField(sku = ''): Promise<void> {
        this.setQueryParams(sku);

        await this.ajaxProvider.fetch();

        this.registerQuantityInput();
        this.mapQuantityInputChange();

        this.registerAdditionalFormElements();
        this.mapAdditionalFormElementChange();

        if (Boolean(sku)) {
            this.quantityInput.focus();
        }
    }

    protected get autocompleteFormClassName(): string {
        return this.getAttribute('autocomplete-form-class-name');
    }
}
