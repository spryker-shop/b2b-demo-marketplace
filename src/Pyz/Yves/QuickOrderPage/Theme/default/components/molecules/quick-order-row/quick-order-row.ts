import QuickOrderRowCore from 'QuickOrderPage/components/molecules/quick-order-row/quick-order-row';
import AutocompleteForm from 'ShopUi/components/molecules/autocomplete-form/autocomplete-form';
import AjaxProvider from 'ShopUi/components/molecules/ajax-provider/ajax-provider';
import debounce from 'lodash-es/debounce';

export default class QuickOrderRow extends QuickOrderRowCore {
    protected incrementButton: HTMLButtonElement;
    protected decrementButton: HTMLButtonElement;

    protected readyCallback(): void {}

    protected init(): void {
        this.ajaxProvider = <AjaxProvider>this.getElementsByClassName(`${this.jsName}__provider`)[0];
        this.autocompleteInput = <AutocompleteForm>this.getElementsByClassName(this.autocompleteFormClassName)[0];

        this.registerQuantityInput();
        this.registerAdditionalFormElements();
        this.mapEvents();
    }

    protected registerQuantityInput(): void {
        this.incrementButton = <HTMLButtonElement>(
            (this.getElementsByClassName(`${this.jsName}__button-increment`)[0] ||
                this.getElementsByClassName(`${this.jsName}-partial__button-increment`)[0])
        );
        this.decrementButton = <HTMLButtonElement>(
            (this.getElementsByClassName(`${this.jsName}__button-decrement`)[0] ||
                this.getElementsByClassName(`${this.jsName}-partial__button-decrement`)[0])
        );

        super.registerQuantityInput();

        // TODO(https://spryker.atlassian.net/browse/CC-23779): Remove variable declaration after integration.
        this.quantityInput = <HTMLInputElement>(
            (this.getElementsByClassName(`${this.jsName}__quantity`)[0] ||
                this.getElementsByClassName(`${this.jsName}-partial__quantity`)[0])
        );
    }

    protected mapAdditionalFormElementChange(): void {
        if (!this.additionalFormElements || !this.additionalFormElements.length) {
            return;
        }

        this.additionalFormElements.forEach((item) => {
            item.addEventListener(
                'change',
                debounce(() => {
                    this.reloadField(this.autocompleteInput.inputValue);
                }, this.autocompleteInput.debounceDelay),
            );
        });
    }

    protected mapQuantityInputChange(): void {
        this.incrementButton.addEventListener('click', (event: Event) => this.incrementValue(event));
        this.decrementButton.addEventListener('click', (event: Event) => this.decrementValue(event));

        super.mapQuantityInputChange();
    }

    protected incrementValue(event: Event): void {
        event.preventDefault();
        const value = Number(this.quantityInput.value);
        const potentialValue = value + this.step;
        if (value < this.maxQuantity) {
            this.quantityInput.value = potentialValue.toString();
            this.reloadField(this.autocompleteInput.inputValue);
        }
    }

    protected decrementValue(event: Event): void {
        event.preventDefault();
        const value = Number(this.quantityInput.value);
        const potentialValue = value - this.step;
        if (potentialValue >= this.minQuantity) {
            this.quantityInput.value = potentialValue.toString();
            this.reloadField(this.autocompleteInput.inputValue);
        }
    }

    async reloadField(sku: string = ''): Promise<void> {
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

    protected get minQuantity(): number {
        return Number(this.quantityInput.getAttribute('min'));
    }

    protected get maxQuantity(): number {
        const max = Number(this.quantityInput.getAttribute('max'));

        return max > 0 && max > this.minQuantity ? max : Infinity;
    }

    protected get step(): number {
        const step = Number(this.quantityInput.getAttribute('step'));

        return step > 0 ? step : 1;
    }

    // TODO(https://spryker.atlassian.net/browse/CC-23779): Remove getter after integration.
    get quantityValue(): string {
        return this.quantityInput.value;
    }
}
