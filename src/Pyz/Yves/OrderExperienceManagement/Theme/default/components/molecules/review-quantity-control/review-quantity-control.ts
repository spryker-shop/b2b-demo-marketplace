import ReviewQuantityControlCore from 'OrderExperienceManagement/components/molecules/review-quantity-control/review-quantity-control';

export default class ReviewQuantityControl extends ReviewQuantityControlCore {
    protected init(): void {
        super.init();
        
        
        this.syncQuantityCounterState();
    }

    protected syncQuantityCounterState(): void {
        const quantityCounterClassName = `${this.getAttribute('quantity-counter-class-name')}`;
        const quantityCounter = this.querySelector<HTMLElement>(`.${quantityCounterClassName}`);

        if (!quantityCounter || !quantityCounterClassName) {
            return;
        }

        quantityCounter.classList.toggle(`${quantityCounterClassName}--disabled`, !!this.removeInput?.checked);
    }
}
