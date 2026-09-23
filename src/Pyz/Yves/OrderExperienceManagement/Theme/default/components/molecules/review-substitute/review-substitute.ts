import ReviewSubstituteCore from 'OrderExperienceManagement/components/molecules/review-substitute/review-substitute';
import ReviewSubstituteSummaryRenderer from './review-substitute-summary-renderer';

export default class ReviewSubstitute extends ReviewSubstituteCore {
    protected init(): void {
        super.init();

        // Replaces the renderer the core init() built. Safe to swap afterwards: init() only ever renders an empty
        // merchant label, which both renderers output identically, and every non-empty label is rendered later
        // from user interaction (onSubstituteConfirmed / onSubstitutePriceUpdated).
        this.summaryRenderer = new ReviewSubstituteSummaryRenderer(this);
    }
}
