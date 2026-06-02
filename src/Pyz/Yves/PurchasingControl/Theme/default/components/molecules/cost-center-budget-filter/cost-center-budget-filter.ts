import CostCenterBudgetFilterCore from 'PurchasingControl/components/molecules/cost-center-budget-filter/cost-center-budget-filter';

export default class CostCenterBudgetFilter extends CostCenterBudgetFilterCore {
    protected filterOptions(): void {
        const selectedValue = this.costCenterSelect.value;
        Array.from(this.budgetSelect.options).forEach((option) => {
            if (!option.value) {
                return;
            }

            if (!selectedValue) {
                option.hidden = false;
                option.disabled = false;

                return;
            }

            option.hidden = option.getAttribute('data-cost-center-id') !== selectedValue;
            option.disabled = option.getAttribute('data-cost-center-id') !== selectedValue;
        });
    }
}
