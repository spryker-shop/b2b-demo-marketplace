import CoreSspServicePointSelector from 'SelfServicePortal/components/molecules/ssp-service-point-selector/ssp-service-point-selector';
import { ServicePointEventDetail } from 'ServicePointWidget/components/molecules/service-point-finder/service-point-finder';

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
        super.changePriceVisibility();

        const offerElement = document.querySelector(`[${this.productDataOfferAttribute}="${offer}"]`);

        if (!offerElement) {
            document.querySelector(`[${this.productDataOfferAttribute}=""]`).classList.remove(this.toggleClassName);
        }
    }
}
