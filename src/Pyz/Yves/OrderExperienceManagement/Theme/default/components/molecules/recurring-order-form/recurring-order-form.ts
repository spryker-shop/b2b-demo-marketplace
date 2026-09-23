import Component from 'ShopUi/models/component';

export default class RecurringOrderForm extends Component {
    protected typeSelect: HTMLSelectElement | null;
    protected valueWrapper: HTMLElement | null;

    protected init(): void {
        this.typeSelect = this.querySelector<HTMLSelectElement>(`.${this.jsName}__type`);
        this.valueWrapper = this.querySelector<HTMLElement>(`.${this.jsName}__value-wrapper`);

        this.toggleValueWrapper();
        this.typeSelect?.addEventListener('change', () => this.toggleValueWrapper());
    }

    protected toggleValueWrapper(): void {
        const isEveryNWeeks = this.typeSelect?.value === this.getAttribute('data-cadence-type-every-n-weeks');
        this.valueWrapper?.classList.toggle('is-hidden', !isEveryNWeeks);
    }
}
