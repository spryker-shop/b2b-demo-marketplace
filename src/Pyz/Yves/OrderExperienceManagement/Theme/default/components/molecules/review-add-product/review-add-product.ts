import CoreReviewAddProduct from 'OrderExperienceManagement/components/molecules/review-add-product/review-add-product';
import { mount } from 'ShopUi/app';

export default class ReviewAddProduct extends CoreReviewAddProduct {
    protected async appendVisibleLine(
        entryIndex: number,
        name: string,
        sku: string,
        unitPrice: number,
        quantity: number,
    ): Promise<void> {
        super.appendVisibleLine();
        await mount();
    }
}
