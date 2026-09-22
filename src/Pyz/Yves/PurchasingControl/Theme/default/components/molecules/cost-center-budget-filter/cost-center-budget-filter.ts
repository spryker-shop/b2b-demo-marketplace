import $ from 'jquery/dist/jquery';
import CostCenterBudgetFilterCore from 'PurchasingControl/components/molecules/cost-center-budget-filter/cost-center-budget-filter';

const COST_CENTER_ID_ATTRIBUTE = 'data-cost-center-id';
const SELECT2_DATA_KEY = 'select2';

export default class CostCenterBudgetFilter extends CostCenterBudgetFilterCore {
    protected filterOptions(): void {
        const selectedValue = this.costCenterSelect.value;

        Array.from(this.budgetSelect.options).forEach((option) => {
            if (!option.value) {
                return;
            }

            const isHidden = Boolean(selectedValue) && option.getAttribute(COST_CENTER_ID_ATTRIBUTE) !== selectedValue;

            option.hidden = isHidden;
            option.disabled = isHidden;
        });

        this.refreshSelect2(this.budgetSelect);
    }

    /**
     * The filter fields are rendered through the custom-select molecule, so select2 keeps its own
     * rendering of the budget field and has to be told that the native select changed.
     */
    protected refreshSelect2(select: HTMLSelectElement): void {
        const $select = $(select);

        if (!$select.data(SELECT2_DATA_KEY)) {
            return;
        }

        $select.trigger('change.select2');
    }
}
