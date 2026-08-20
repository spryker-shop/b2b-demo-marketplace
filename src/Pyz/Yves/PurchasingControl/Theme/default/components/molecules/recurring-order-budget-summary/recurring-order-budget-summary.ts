import $ from 'jquery/dist/jquery';
import RecurringOrderBudgetSummaryCore from 'PurchasingControl/components/molecules/recurring-order-budget-summary/recurring-order-budget-summary';

const COST_CENTER_ID_ATTRIBUTE = 'data-cost-center-id';
const SELECT2_DATA_KEY = 'select2';

export default class RecurringOrderBudgetSummary extends RecurringOrderBudgetSummaryCore {
    protected filterBudgetOptions(): void {
        const selectedCostCenter = this.costCenterSelect.value;

        Array.from(this.budgetSelect.options).forEach((option: HTMLOptionElement) => {
            if (!option.value) {
                return;
            }

            const isHidden =
                Boolean(selectedCostCenter) && option.getAttribute(COST_CENTER_ID_ATTRIBUTE) !== selectedCostCenter;

            option.hidden = isHidden;
            option.disabled = isHidden;
        });

        this.refreshSelect2(this.budgetSelect);
    }

    protected refreshSelect2(select: HTMLSelectElement): void {
        const $select = $(select);

        if (!$select.data(SELECT2_DATA_KEY)) {
            return;
        }

        $select.trigger('change.select2');
    }
}
