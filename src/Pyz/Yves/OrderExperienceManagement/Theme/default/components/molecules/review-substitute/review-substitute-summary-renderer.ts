import Component from 'ShopUi/models/component';
import ReviewSubstituteSummaryRendererCore from 'OrderExperienceManagement/components/molecules/review-substitute/review-substitute-summary-renderer';

export default class ReviewSubstituteSummaryRenderer extends ReviewSubstituteSummaryRendererCore {
    constructor(host: Component, previous?: ReviewSubstituteSummaryRendererCore) {
        super(host);

        if (previous) {
            const { changeButtonColumn, changeButtonHome } = previous as ReviewSubstituteSummaryRenderer;

            this.changeButtonColumn = changeButtonColumn;
            this.changeButtonHome = changeButtonHome;
        }
    }

    protected renderMerchantLabel(merchantLabel: string): void {
        this.renderText(this.merchantElement, merchantLabel === '' ? '' : `${merchantLabel}`);
    }

    showAppliedState(): void {
        this.toggleAppliedElements(true);
        this.moveChangeButtonBeforeRemove();

        const buttonTemplate = document.getElementById('change-button-placeholder') as HTMLTemplateElement | null;
        const buttonContent = buttonTemplate?.content.cloneNode(true);
        if (this.changeButton && buttonContent) {
            this.changeButton.replaceChildren(buttonContent);
        }
    }
}
