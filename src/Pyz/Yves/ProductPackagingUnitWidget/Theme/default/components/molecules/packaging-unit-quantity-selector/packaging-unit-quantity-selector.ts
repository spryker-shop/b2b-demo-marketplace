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
        const newPriceValueBlock = this.querySelector<HTMLElement>(
            `.${this.jsName}__product-packaging-new-price-value-block`,
        );

        if (!volumePrice || !newPriceBlock || !newPriceValueBlock) {
            return;
        }

        const hasNewPrice = !newPriceBlock.classList.contains('is-hidden');
        newPriceBlock.classList.add('is-hidden');

        if (hasNewPrice) {
            const amount = newPriceValueBlock.innerText.replace(/[^\d.,]/g, '');
            volumePrice.changePrice(volumePrice.originalPrice.replace(/[\d.,]+/, amount));

            return;
        }

        volumePrice.changePrice(volumePrice.originalPrice);
    }

    protected get visibleVolumePrice(): VolumePrice | null {
        const visibleOffer = document.querySelector<VolumePrice>(
            '.product-configurator__price-block [data-product-price-offer]:not(.is-hidden) volume-price',
        );

        return visibleOffer ?? document.querySelector<VolumePrice>('.product-configurator__price-block volume-price');
    }
}
