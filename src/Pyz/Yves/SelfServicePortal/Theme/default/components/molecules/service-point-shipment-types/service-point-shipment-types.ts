import CoreServicePointShipmentTypes, {
    EVENT_SHIPMENT_TYPE_CHANGE,
} from 'SelfServicePortal/components/molecules/service-point-shipment-types/service-point-shipment-types';

export default class ServicePointShipmentTypes extends CoreServicePointShipmentTypes {
    protected toggle(event: Event): void {
        this.dispatchCustomEvent(EVENT_SHIPMENT_TYPE_CHANGE, null, { bubbles: true });
        super.toggle(event);
    }
}
