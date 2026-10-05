import ReviewSubstituteCore from 'OrderExperienceManagement/components/molecules/review-substitute/review-substitute';
import ReviewSubstituteSummaryRenderer from './review-substitute-summary-renderer';

export default class ReviewSubstitute extends ReviewSubstituteCore {
    protected init(): void {
        super.init();

        this.summaryRenderer = new ReviewSubstituteSummaryRenderer(this, this.summaryRenderer);

        if (this.hasEntry) {
            this.summaryRenderer.showAppliedState();
        }
    }
}
