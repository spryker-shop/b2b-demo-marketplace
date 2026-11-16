import PackagingUnitQuantitySelectorCore from 'ProductPackagingUnitWidget/components/molecules/packaging-unit-quantity-selector/packaging-unit-quantity-selector';
import VolumePrice from 'PriceProductVolumeWidget/components/molecules/volume-price/volume-price';

export default class PackagingUnitQuantitySelector extends PackagingUnitQuantitySelectorCore {
    protected readonly priceFractionDigits = 2;
    protected quantityNote: HTMLElement | null;
    protected quantityMaxNote: HTMLElement | null;

    protected async init(): Promise<void> {
        this.quantityNote = this.querySelector<HTMLElement>(`.${this.jsName}__quantity-note`);
        this.quantityMaxNote = this.querySelector<HTMLElement>(`.${this.jsName}__quantity-max-note`);

        await super.init();

        if (this.qtyInSalesUnitInput && this.currentSalesUnit) {
            this.toggleQuantityMaxNote(
                this.multiply(Number(this.qtyInSalesUnitInput.value), Number(this.currentSalesUnit.conversion)),
            );
        }
    }

    protected qtyInputChange(qtyInSalesUnits?: number): void {
        super.qtyInputChange(qtyInSalesUnits);

        const qtyInSalesUnitsValue = qtyInSalesUnits ?? this.formattedQtyInSalesUnitInput.unformattedValue;

        this.toggleQuantityMaxNote(this.multiply(qtyInSalesUnitsValue, Number(this.currentSalesUnit.conversion)));
    }

    protected selectQty(qtyInBaseUnits: number, qtyInSalesUnits: number): void {
        super.selectQty(qtyInBaseUnits, qtyInSalesUnits);
        this.toggleQuantityMaxNote(qtyInBaseUnits);
    }

    protected toggleQuantityMaxNote(qtyInBaseUnits: number): void {
        if (!this.quantityMaxNote) {
            return;
        }

        const isMaxQuantityReached = this.getMaxQuantity() > 0 && qtyInBaseUnits >= this.getMaxQuantity();

        this.quantityMaxNote.classList.toggle('is-hidden', !isMaxQuantityReached);
        this.quantityNote?.classList.toggle('is-hidden', isMaxQuantityReached);
    }

    protected multiply(a: number, b: number): number {
        const result = a * b;
        const precision = 1000;

        return Math.round(result * precision) / precision;
    }

    protected priceCalculation(amountInBaseUnits: number): void {
        super.priceCalculation(amountInBaseUnits);

        const volumePrice = this.visibleVolumePrice;
        const newPriceBlock = this.querySelector<HTMLElement>(`.${this.jsName}__product-packaging-new-price-block`);

        if (!volumePrice) {
            return;
        }

        newPriceBlock?.classList.add('is-hidden');

        const originalPrice = volumePrice.getPriceForQuantity(Number(this.qtyInSalesUnitInput?.value));
        const quantity = Number(this.qtyInBaseUnitInput?.value);
        const defaultAmount = Number(this.amountDefaultInBaseUnitInput?.value);
        const amountRatio = defaultAmount > 0 ? amountInBaseUnits / defaultAmount : 1;
        const totalValue = this.parsePriceAmount(originalPrice) * amountRatio * quantity;

        if (!Number.isFinite(totalValue) || totalValue <= 0) {
            volumePrice.changePrice(originalPrice);

            return;
        }

        volumePrice.changePrice(this.replacePriceAmount(originalPrice, totalValue));
    }

    protected parsePriceAmount(price: string): number {
        const amount = price.match(/[\d.,]+/)?.[0] ?? '';
        const decimalSeparator = this.getPriceDecimalSeparator(amount);

        if (!decimalSeparator) {
            return Number(amount.replace(/[.,]/g, ''));
        }

        const thousandSeparator = decimalSeparator === '.' ? ',' : '.';

        return Number(amount.split(thousandSeparator).join('').replace(decimalSeparator, '.'));
    }

    protected replacePriceAmount(price: string, value: number): string {
        const amount = price.match(/[\d.,]+/)?.[0] ?? '';
        const decimalSeparator = this.getPriceDecimalSeparator(amount) ?? '.';

        return price.replace(/[\d.,]+/, value.toFixed(this.priceFractionDigits).replace('.', decimalSeparator));
    }

    protected getPriceDecimalSeparator(amount: string): string | null {
        return amount.match(/([.,])\d{1,2}$/)?.[1] ?? null;
    }

    protected get visibleVolumePrice(): VolumePrice | null {
        const visibleOffer = document.querySelector<VolumePrice>(
            '.product-configurator__price-block [data-product-price-offer]:not(.is-hidden) volume-price',
        );

        return visibleOffer ?? document.querySelector<VolumePrice>('.product-configurator__price-block volume-price');
    }
}
