import ValidateNextCheckoutStepCore from 'CheckoutPage/components/molecules/validate-next-checkout-step/validate-next-checkout-step';

export default class ValidateNextCheckoutStep extends ValidateNextCheckoutStepCore {
    protected mapEvents(): void {
        super.mapEvents();
        document.querySelector(`.${this.getAttribute('same-billing-class-name')} input[type="checkbox"]`)?.addEventListener('change', (event) => this.toggleDisablingNextStepButton(event, true));
    }

    protected get isDropdownTriggerPreSelected(): boolean {
        if (!this.dropdownTriggers) {
            return false;
        }

        return this.dropdownTriggers.some(
            (element: HTMLSelectElement) => element.closest('is-hidden') && !element.value,
        );
    }

    protected toggleDisablingNextStepButton(event: Event, checkbox: boolean): void {
        if (!this.target) {
            return;
        }

        if (checkbox) {
            this.disableNextStepButton(!(event.target as HTMLInputElement).checked)

            return;
        }

        const isFormInvalid =
            this.isFormFieldsEmpty || this.isDropdownTriggerPreSelected || this.isExtraTriggersUnchecked;
        this.disableNextStepButton(isFormInvalid);
    }

}
