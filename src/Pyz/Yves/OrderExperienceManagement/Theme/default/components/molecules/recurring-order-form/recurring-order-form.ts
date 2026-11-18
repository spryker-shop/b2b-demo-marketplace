import Component from 'ShopUi/models/component';

export default class RecurringOrderForm extends Component {
    protected typeSelect: HTMLSelectElement | null;
    protected valueWrapper: HTMLElement | null;
    protected daysWrapper: HTMLElement | null;
    protected dayInputs: HTMLInputElement[];

    protected init(): void {
        this.typeSelect = this.querySelector<HTMLSelectElement>(`.${this.jsName}__type`);
        this.valueWrapper = this.querySelector<HTMLElement>(`.${this.jsName}__value-wrapper`);
        this.daysWrapper = this.querySelector<HTMLElement>(`.${this.jsName}__days-wrapper`);
        this.dayInputs = Array.from(this.querySelectorAll<HTMLInputElement>(`.${this.jsName}__day-input`));

        this.toggleValueWrapper();
        this.toggleDaysWrapper();

        this.typeSelect?.addEventListener('change', () => {
            this.toggleValueWrapper();
            this.toggleDaysWrapper();
        });

        this.dayInputs.forEach((dayInput: HTMLInputElement) =>
            dayInput.addEventListener('change', () => this.clearDaysError()),
        );
    }

    protected toggleValueWrapper(): void {
        const isEveryNWeeks = this.typeSelect?.value === this.getAttribute('data-cadence-type-every-n-weeks');
        this.valueWrapper?.classList.toggle('is-hidden', !isEveryNWeeks);
    }

    protected toggleDaysWrapper(): void {
        const isByDays = this.typeSelect?.value === this.getAttribute('data-cadence-type-by-days');
        this.daysWrapper?.classList.toggle('is-hidden', !isByDays);
    }

    protected clearDaysError(): void {
        if (!this.dayInputs.some((dayInput: HTMLInputElement) => dayInput.checked)) {
            return;
        }

        this.daysWrapper?.querySelector('.form__error')?.remove();
    }
}
