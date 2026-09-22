import ValidateNextCheckoutStepCore from 'CheckoutPage/components/molecules/validate-next-checkout-step/validate-next-checkout-step';

export default class ValidateNextCheckoutStep extends ValidateNextCheckoutStepCore {
    protected mapEvents(): void {
        super.mapEvents();
        document
            .querySelector(`.${this.getAttribute('same-billing-class-name')} input[type="checkbox"]`)
            ?.addEventListener('change', () => this.onTriggerInput());
    }

    protected get isDropdownTriggerPreSelected(): boolean {
        if (!this.dropdownTriggers) {
            return false;
        }

        return this.dropdownTriggers.some(
            (element: HTMLSelectElement) => !element.closest('.is-hidden') && !element.value,
        );
    }
}
