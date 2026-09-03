import ReviewSubstituteSummaryRendererCore from 'OrderExperienceManagement/components/molecules/review-substitute/review-substitute-summary-renderer';

export default class ReviewSubstituteSummaryRenderer extends ReviewSubstituteSummaryRendererCore {
    protected renderMerchantLabel(merchantLabel: string): void {
        this.renderText(this.merchantElement, merchantLabel === '' ? '' : `${merchantLabel}`);
    }
}
