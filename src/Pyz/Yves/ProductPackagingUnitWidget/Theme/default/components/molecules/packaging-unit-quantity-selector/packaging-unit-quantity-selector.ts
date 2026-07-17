import PackagingUnitQuantitySelectorCore from 'ProductPackagingUnitWidget/components/molecules/packaging-unit-quantity-selector/packaging-unit-quantity-selector';
import { VolumePrice } from 'PriceProductVolumeWidget/components/molecules/volume-price/volume-price';

export default class PackagingUnitQuantitySelector extends PackagingUnitQuantitySelectorCore {
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

        return price.replace(/[\d.,]+/, value.toFixed(2).replace('.', decimalSeparator));
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
