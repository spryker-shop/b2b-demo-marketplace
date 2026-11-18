import CoreReviewAddProduct from 'OrderExperienceManagement/components/molecules/review-add-product/review-add-product';
import { EVENT_ADDED_ITEM_UPDATE } from 'OrderExperienceManagement/components/molecules/review-added-items/review-added-items';
import { mount } from 'ShopUi/app';

export default class ReviewAddProduct extends CoreReviewAddProduct {
    protected async appendVisibleLine(
        entryIndex: number,
        name: string,
        sku: string,
        unitPrice: number,
        quantity: number,
    ): Promise<void> {
        if (!this.linesContainer) {
            return;
        }

        const lineElement = this.lineRenderer.render({
            entryIndex,
            name,
            sku,
            unitPrice,
            quantity,
            onQuantityChange: (lineQuantity: number) => {
                this.dispatchCustomEvent(
                    EVENT_ADDED_ITEM_UPDATE,
                    { entryKey: String(entryIndex), values: { quantity: lineQuantity } },
                    { bubbles: true },
                );
                this.triggerRecalculation();
            },
            onRemove: (element: HTMLElement) => this.removeEntry(entryIndex, element),
        });

        if (!lineElement) {
            return;
        }

        this.linesContainer.appendChild(lineElement);
        this.triggerRecalculation();

        await mount();
    }
}
