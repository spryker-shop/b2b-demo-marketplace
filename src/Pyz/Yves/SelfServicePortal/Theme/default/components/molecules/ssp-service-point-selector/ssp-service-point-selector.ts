import CoreSspServicePointSelector from 'SelfServicePortal/components/molecules/ssp-service-point-selector/ssp-service-point-selector';
import { ServicePointEventDetail } from 'ServicePointWidget/components/molecules/service-point-finder/service-point-finder';
import { EVENT_SHIPMENT_TYPE_CHANGE } from 'SelfServicePortal/components/molecules/service-point-shipment-types/service-point-shipment-types';

declare module 'ServicePointWidget/components/molecules/service-point-finder/service-point-finder' {
    interface ProductOfferAvailability {
        name: string;
    }
}

export default class SspServicePointSelector extends CoreSspServicePointSelector {
    protected onServicePointSelected(detail: ServicePointEventDetail): void {
        super.onServicePointSelected(detail);
        this.location.innerHTML = `${detail.productOfferAvailability[0].name} <br> ${detail.address}`;
    }

    protected changePriceVisibility(offer: string): void {
        super.changePriceVisibility(offer);

        const offerPrice = document.querySelector(`[${this.productDataOfferAttribute}="${offer}"]`);

        if (!offerPrice) {
            document.querySelector(`[${this.productDataOfferAttribute}=""]`).classList.remove(this.toggleClassName);
        }
    }

    protected onShipmentTypeChange(): void {
        document.addEventListener(EVENT_SHIPMENT_TYPE_CHANGE, () => {
            queueMicrotask(() => {
                const offerElement = document.querySelector<HTMLInputElement>(`${this.offerReferenceSelector}:checked`);

                if (offerElement) {
                    offerElement.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    this.changePriceVisibility('');
                }
            })
        });
    }
}
