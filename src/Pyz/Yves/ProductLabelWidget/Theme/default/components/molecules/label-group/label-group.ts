import LabelGroupCore from 'ProductLabelWidget/components/molecules/label-group/label-group';
import { ProductItemLabelsData } from 'ShopUi/components/molecules/product-item/product-item';

export default class LabelGroup extends LabelGroupCore {
    protected allLabelsWrapper: HTMLElement;
    protected labelCounter: HTMLElement;

    protected init(): void {
        super.init();
        this.allLabelsWrapper = <HTMLElement>this.getElementsByClassName(`${this.name}__all-labels-wrapper`)[0];
        this.labelCounter = <HTMLElement>this.getElementsByClassName(`${this.name}__counter`)[0];
    }

    setProductLabels(labels: ProductItemLabelsData[]) {
        if (!labels.length) {
            this.productLabelFlags.forEach((element: HTMLElement) => element.classList.add(this.classToToggle));
            this.updateExtrasVisibility(0);

            return;
        }

        this.updateProductLabels(labels);
        this.updateExtrasVisibility(labels.length);
    }

    protected updateProductLabels(labelFlags: ProductItemLabelsData[]): void {
        labelFlags.forEach((element: ProductItemLabelsData, index: number) => {
            if (index) {
                this.createProductLabelFlagClones();
            }

            this.deleteProductLabelFlagClones(labelFlags);
            this.deleteProductLabelFlagModifiers(index);
            this.updateProductLabelFlags(element, index);
        });
    }

    protected createProductLabelFlagClones(): void {
        const cloneLabelFlag = this.productLabelFlags[0].cloneNode(true);

        if (this.allLabelsWrapper) {
            this.allLabelsWrapper.appendChild(cloneLabelFlag);
        } else {
            this.productLabelFlags[0].parentNode.insertBefore(cloneLabelFlag, this.productLabelFlags[0].nextSibling);
        }

        this.productLabelFlags = <HTMLElement[]>Array.from(this.getElementsByClassName(`${this.jsName}__label-flag`));
    }

    protected updateExtrasVisibility(count: number): void {
        const extraCount = Math.max(count - 1, 0);

        if (this.labelCounter) {
            this.labelCounter.textContent = ` + ${extraCount}`;
            this.labelCounter.style.display = extraCount === 0 ? 'none' : '';
        }

        if (this.allLabelsWrapper) {
            this.allLabelsWrapper.style.display = extraCount === 0 ? 'none' : '';
        }
    }
}
