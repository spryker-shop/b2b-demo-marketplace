import PriceToleranceBulkBarCore from 'OrderExperienceManagement/components/molecules/price-tolerance-bulk-bar/price-tolerance-bulk-bar';

export const EVENT_BULK_CLEAR = 'price-tolerance-bulk-clear';

export default class PriceToleranceBulkBar extends PriceToleranceBulkBarCore {
    protected mapEvents(): void {
        super.mapEvents();

        this.querySelector(`.${this.jsName}__clear`)?.addEventListener('click', () => this.clear());
    }

    protected clear(): void {
        this.dispatchEvent(new CustomEvent(EVENT_BULK_CLEAR));
    }
}
