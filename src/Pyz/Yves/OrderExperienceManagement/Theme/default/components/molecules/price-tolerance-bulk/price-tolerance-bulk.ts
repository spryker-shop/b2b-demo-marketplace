import PriceToleranceBulkCore from 'OrderExperienceManagement/components/molecules/price-tolerance-bulk/price-tolerance-bulk';

const ORDER_TOTAL_ONLY_MODIFIER = 'order-total-only-mode';
const ORDER_TOLERANCE_SELECTOR = '.js-price-tolerance__order-tolerance';

export default class PriceToleranceBulk extends PriceToleranceBulkCore {
    protected ignoreField: HTMLInputElement | null;
    protected productsModeInput: HTMLInputElement | null;
    protected orderTotalModeInput: HTMLInputElement | null;
    protected modeHint: HTMLElement | null;
    protected orderToleranceInput: HTMLInputElement | null;
    protected orderToleranceValues: HTMLElement[];

    protected init(): void {
        this.ignoreField = this.querySelector(`.${this.jsName}__ignore-field`);
        this.productsModeInput = this.querySelector(`.${this.jsName}__mode-input--products`);
        this.orderTotalModeInput = this.querySelector(`.${this.jsName}__mode-input--order_total`);
        this.modeHint = this.querySelector(`.${this.jsName}__mode-hint`);
        this.orderToleranceInput = this.closest('form')?.querySelector(ORDER_TOLERANCE_SELECTOR) ?? null;
        this.orderToleranceValues = Array.from(this.querySelectorAll(`.${this.jsName}__order-tolerance-value`));

        this.applyMode();

        super.init();
    }

    protected mapEvents(): void {
        super.mapEvents();

        [this.productsModeInput, this.orderTotalModeInput].forEach((input: HTMLInputElement | null) =>
            input?.addEventListener('change', () => this.applyMode()),
        );
        this.orderToleranceInput?.addEventListener('input', () => this.syncOrderToleranceAvailability());
    }

    protected applyMode(): void {
        const isOrderTotalOnly = this.orderTotalModeInput?.checked ?? false;

        if (this.ignoreField) {
            this.ignoreField.checked = isOrderTotalOnly;
        }

        this.classList.toggle(`${this.name}--${ORDER_TOTAL_ONLY_MODIFIER}`, isOrderTotalOnly);
    }

    /**
     * Ignoring product prices only takes effect with an order total tolerance above 0,
     * so the option follows the order total field.
     */
    protected syncOrderToleranceAvailability(): void {
        const value = this.orderToleranceInput?.value ?? '';
        const hasOrderTolerance = parseFloat(value) > 0;

        this.orderToleranceValues.forEach((element: HTMLElement) => {
            element.textContent = hasOrderTolerance ? value : '0';
        });

        if (this.modeHint) {
            this.modeHint.hidden = hasOrderTolerance;
        }

        if (!this.orderTotalModeInput) {
            return;
        }

        this.orderTotalModeInput.disabled = !hasOrderTolerance;

        if (!hasOrderTolerance && this.orderTotalModeInput.checked && this.productsModeInput) {
            this.productsModeInput.checked = true;
            this.applyMode();
        }
    }
}
