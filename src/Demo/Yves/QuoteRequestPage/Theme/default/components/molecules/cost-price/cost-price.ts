import Component from 'ShopUi/models/component';

export default class CostPrice extends Component {
    protected container: HTMLElement | null = null;
    protected sourcePriceForm: HTMLElement | null = null;
    protected useDefaultPriceCheckbox: HTMLInputElement | null = null;
    protected customPriceInput: HTMLInputElement | null = null;
    protected customPriceHiddenInput: HTMLInputElement | null = null;
    protected marginEl: HTMLElement | null = null;

    protected readonly fractionalMonetaryUnit: number = 100;

    protected readyCallback(): void {}

    protected init(): void {
        this.container = <HTMLElement>this.closest('.quote-request-cart-item__column');
        if (!this.container) {
            return;
        }

        this.sourcePriceForm = this.container.querySelector('source-price-form');
        this.useDefaultPriceCheckbox = this.container.querySelector('.js-source-price-form__checkbox-container');
        this.customPriceInput = this.container.querySelector(
            '.js-source-price-form__input-container .js-formatted-number-input__input',
        );
        this.customPriceHiddenInput = this.container.querySelector(
            '.js-source-price-form__input-container .js-formatted-number-input__hidden-input',
        );
        this.marginEl = <HTMLElement>this.getElementsByClassName(`${this.jsName}__gross-margin`)[0];

        if (!this.marginEl) {
            return;
        }

        this.bindEvents();
        this.renderMargin();
        // Defer one tick so sibling components (source-price-form, formatted-number-input)
        // have finished their own init() and populated hidden values from saved data.
        window.setTimeout(() => this.renderMargin(), 0);
    }

    protected bindEvents(): void {
        if (this.useDefaultPriceCheckbox) {
            this.useDefaultPriceCheckbox.addEventListener('change', () => this.renderMargin());
        }

        if (this.customPriceInput) {
            this.customPriceInput.addEventListener('input', () => this.renderMargin());
            this.customPriceInput.addEventListener('change', () => this.renderMargin());
        }

        if (this.customPriceHiddenInput) {
            this.customPriceHiddenInput.addEventListener('input', () => this.renderMargin());
            this.customPriceHiddenInput.addEventListener('change', () => this.renderMargin());
        }
    }

    protected renderMargin(): void {
        if (!this.hasCostPrice) {
            this.printUnavailable();

            return;
        }

        const sellingPrice = this.getEffectiveSellingPrice();
        if (sellingPrice === null || sellingPrice <= 0) {
            this.printMargin(0);

            return;
        }

        const margin = ((sellingPrice - this.costPrice) / sellingPrice) * this.fractionalMonetaryUnit;
        this.printMargin(margin);
    }

    protected getEffectiveSellingPrice(): number | null {
        if (this.isDefaultPriceActive()) {
            const defaultPrice = this.defaultPrice;

            return defaultPrice > 0 ? defaultPrice : null;
        }

        return this.customPriceValue;
    }

    protected isDefaultPriceActive(): boolean {
        if (!this.useDefaultPriceCheckbox) {
            return true;
        }

        return this.useDefaultPriceCheckbox.checked;
    }

    protected printMargin(margin: number): void {
        if (!this.marginEl) {
            return;
        }
        this.marginEl.innerText = `${Math.floor(margin)}%`;
    }

    protected printUnavailable(): void {
        if (!this.marginEl) {
            return;
        }
        const label = this.marginEl.getAttribute('data-unavailable-label') ?? '—';
        this.marginEl.innerText = label;
    }

    protected get hasCostPrice(): boolean {
        const raw = this.getAttribute('cost-price');
        if (raw === null || raw === '') {
            return false;
        }
        const value = Number(raw);

        return Number.isFinite(value) && value > 0;
    }

    protected get customPriceValue(): number | null {
        const hiddenValue = this.parseNumber(this.customPriceHiddenInput?.value);
        if (hiddenValue !== null) {
            return hiddenValue;
        }

        return this.parseNumber(this.customPriceInput?.value);
    }

    protected parseNumber(raw: string | undefined): number | null {
        if (!raw || raw.length === 0) {
            return null;
        }

        const normalized = raw.replace(/[^\d.,-]/g, '').replace(',', '.');
        if (normalized.length === 0) {
            return null;
        }

        const value = Number(normalized);

        return Number.isFinite(value) ? value : null;
    }

    protected get defaultPrice(): number {
        return Number(this.getAttribute('original-price')) / this.fractionalMonetaryUnit;
    }

    protected get costPrice(): number {
        return Number(this.getAttribute('cost-price')) / this.fractionalMonetaryUnit;
    }
}
