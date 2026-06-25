import Component from 'ShopUi/models/component';

export default class VolumePriceTable extends Component {
    protected rows: HTMLElement[];
    protected quantityElement: HTMLInputElement;
    protected currentClass: string;


    protected init(): void {
        this.rows = Array.from(this.getElementsByClassName(`${this.jsName}__row`)) as HTMLElement[];
        this.quantityElement = document.getElementsByClassName(
            this.getAttribute('quantity-class-name')!,
        )[0] as HTMLInputElement;
        this.currentClass = `${this.name}__row--current`;

        if (this.quantityElement) {
            this.mapEvents();
        }
    }

    protected mapEvents(): void {
        this.quantityElement.addEventListener('change', () => this.onQuantityChange());
        this.quantityElement.addEventListener('quantityChange', () => this.onQuantityChange());
    }

    protected onQuantityChange(): void {
        const quantity = Number(this.quantityElement.value);

        if (!Number.isFinite(quantity)) {
            return;
        }

        this.highlightMatchingRow(quantity);
    }

    protected highlightMatchingRow(quantity: number): void {
        this.rows.forEach((row) => {
            const min = Number(row.dataset.minQuantity);
            const hasMax = row.dataset.maxQuantity !== undefined;
            const max = hasMax ? Number(row.dataset.maxQuantity) : Infinity;
            const isMatch = quantity >= min && quantity <= max;

            row.classList.toggle(this.currentClass, isMatch);
        });
    }
}
