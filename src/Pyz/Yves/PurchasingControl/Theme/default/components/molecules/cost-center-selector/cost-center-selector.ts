import $ from 'jquery/dist/jquery';
import CostCenterSelectorCore from 'PurchasingControl/components/molecules/cost-center-selector/cost-center-selector';

const SELECT2_DATA_KEY = 'select2';
const HIDDEN_CLASS_NAME = 'is-hidden';
const NEGATIVE_AMOUNT_PATTERN = /[-−]/;

enum BudgetState {
    None = 'none',
    Applied = 'applied',
    NotApplied = 'not-applied',
    OverBudget = 'over-budget',
}

export default class CostCenterSelector extends CostCenterSelectorCore {
    protected sourceCostCenterFieldValue: string;
    protected statusElement: HTMLElement;
    protected usageElement: HTMLElement;
    protected progressElement: HTMLElement;
    protected footerElement: HTMLElement;
    protected applyElement: HTMLElement;
    protected badgeElements: Record<string, HTMLElement | null>;
    protected hintElements: Record<string, HTMLElement | null>;

    protected init(): void {
        this.sourceCostCenterFieldValue = this.querySelector<HTMLSelectElement>(
            `.${this.jsName}__cost-center-control`,
        )?.value;
        this.statusElement = this.querySelector<HTMLElement>(`.${this.name}__status`);
        this.usageElement = this.querySelector<HTMLElement>(`.${this.jsName}__budget-usage`);
        this.progressElement = this.querySelector<HTMLElement>(`.${this.jsName}__budget-progress`);
        this.footerElement = this.querySelector<HTMLElement>(`.${this.jsName}__footer`);
        this.applyElement = this.querySelector<HTMLElement>(`.${this.jsName}__apply`);
        this.badgeElements = this.queryByState('badge');
        this.hintElements = this.queryByState('hint');

        super.init();
    }

    /**
     * The choice fields are rendered through the custom-select molecule, so select2 keeps its own
     * rendering of the budget field and has to be told that the native select changed.
     */
    protected filterBudgetsByCostCenter(): void {
        super.filterBudgetsByCostCenter();
        this.refreshSelect2(this.budgetField);
    }

    /**
     * Replaces the vendor apply warning: the badge, the status strip and the footer follow the selection.
     */
    protected toggleWarning(): void {
        super.toggleWarning();
        this.updateState();
    }

    protected updateState(): void {
        const state = this.getState();
        const isApplied = state === BudgetState.Applied;
        const isChanged = this.isSelectionChanged();

        Object.keys(this.badgeElements).forEach((name: string) => {
            this.badgeElements[name]?.classList.toggle(HIDDEN_CLASS_NAME, name !== state);
        });

        Object.keys(this.hintElements).forEach((name: string) => {
            this.hintElements[name]?.classList.toggle(HIDDEN_CLASS_NAME, name !== state);
        });

        this.statusElement?.classList.toggle(
            `${this.name}__status--over-budget`,
            NEGATIVE_AMOUNT_PATTERN.test(this.budgetRemaining?.textContent ?? ''),
        );

        // Used and total amounts are rendered for the applied budget only, other options carry just the remaining amount.
        this.usageElement?.classList.toggle(HIDDEN_CLASS_NAME, !isApplied);
        this.progressElement?.classList.toggle(HIDDEN_CLASS_NAME, !isApplied);
        this.footerElement?.classList.toggle(HIDDEN_CLASS_NAME, state === BudgetState.None && !isChanged);
        this.applyElement?.classList.toggle(HIDDEN_CLASS_NAME, !isChanged);
    }

    protected getState(): BudgetState {
        if (!this.budgetField.value) {
            return BudgetState.None;
        }

        if (!this.isSelectionChanged()) {
            return BudgetState.Applied;
        }

        return NEGATIVE_AMOUNT_PATTERN.test(this.budgetRemaining?.textContent ?? '')
            ? BudgetState.OverBudget
            : BudgetState.NotApplied;
    }

    protected isSelectionChanged(): boolean {
        return (
            this.budgetField.value !== this.sourceBudgetFieldValue ||
            (this.costCenterField?.value ?? '') !== (this.sourceCostCenterFieldValue ?? '')
        );
    }

    protected queryByState(type: 'badge' | 'hint'): Record<string, HTMLElement | null> {
        return Object.values(BudgetState).reduce(
            (elements: Record<string, HTMLElement | null>, state: BudgetState) => ({
                ...elements,
                [state]: this.querySelector<HTMLElement>(`.${this.jsName}__${type}-${state}`),
            }),
            {},
        );
    }

    protected refreshSelect2(select: HTMLSelectElement): void {
        const $select = $(select);

        if (!$select.data(SELECT2_DATA_KEY)) {
            return;
        }

        $select.trigger('change.select2');
    }
}
