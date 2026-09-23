import $ from 'jquery/dist/jquery';
import CostCenterSelectorCore from 'PurchasingControl/components/molecules/cost-center-selector/cost-center-selector';

const SELECT2_DATA_KEY = 'select2';

export default class CostCenterSelector extends CostCenterSelectorCore {
    /**
     * The choice fields are rendered through the custom-select molecule, so select2 keeps its own
     * rendering of the budget field and has to be told that the native select changed.
     */
    protected filterBudgetsByCostCenter(): void {
        super.filterBudgetsByCostCenter();
        this.refreshSelect2(this.budgetField);
    }

    protected refreshSelect2(select: HTMLSelectElement): void {
        const $select = $(select);

        if (!$select.data(SELECT2_DATA_KEY)) {
            return;
        }

        $select.trigger('change.select2');
    }
}
