import $ from 'jquery/dist/jquery';
import ReviewShipmentSelectionCore from 'OrderExperienceManagement/components/molecules/review-shipment-selection/review-shipment-selection';

const SELECT2_DATA_KEY = 'select2';

export default class ReviewShipmentSelection extends ReviewShipmentSelectionCore {
    reset(): void {
        super.reset();

        this.refreshSelect2(this.addressSelect);
    }

    clearMethods(): void {
        this.destroySelect2(this.methodSelect);

        super.clearMethods();
    }

    /** Select2 renders its own label, so the native value reset has to be pushed into the widget. */
    protected refreshSelect2(select: HTMLSelectElement | null): void {
        const $select = $(select);

        if (!select || !$select.data(SELECT2_DATA_KEY)) {
            return;
        }

        $select.trigger('change.select2');
    }

    /** Select2 keeps its dropdown attached to the body, so it must be destroyed before the markup is discarded. */
    protected destroySelect2(select: HTMLSelectElement | null): void {
        const $select = $(select);

        if (!select || !$select.data(SELECT2_DATA_KEY)) {
            return;
        }

        $select.select2('destroy');
    }
}
