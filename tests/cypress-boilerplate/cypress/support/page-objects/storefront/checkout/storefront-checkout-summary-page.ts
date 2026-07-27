import { AbstractPage } from '../../abstract-page'

const summaryForm = 'form[name="summaryForm"]'

export class StorefrontCheckoutSummaryPage extends AbstractPage {
  protected PAGE_URL = Cypress.env('STOREFRONT_URL') + 'en/checkout/summary'

  getAcceptTermsAndConditionsCheckbox = (): Cypress.Chainable => {
    return cy.get(summaryForm).find('input[name="acceptTermsAndConditions"]')
  }

  getGrandTotal = (): Cypress.Chainable => {
    // the order summary totals live in a sibling block before <form name="summaryForm">,
    // not inside it
    return cy
      .get('[data-qa="component summary-overview"]')
      .find('.summary-overview__item--total')
  }

  getGrandTotalAmount = (): Cypress.Chainable => {
    return this.getGrandTotal().find(
      'strong.summary-overview__title--color-gray'
    )
  }

  // "Cost Center Control" widget (Purchasing Control feature) — only rendered when the
  // customer's business unit has active cost centers requiring selection before checkout
  getCostCenterSelect = (): Cypress.Chainable => {
    return cy.get('#idCostCenter')
  }

  getBudgetSelect = (): Cypress.Chainable => {
    return cy.get('#idBudget')
  }

  getCostCenterApplyButton = (): Cypress.Chainable => {
    return cy.get('cost-center-selector').find('button[type="submit"]')
  }

  selectCostCenter = (costCenterName: string): void => {
    // the cost-center <select> submits its form on change (full page reload) to fetch
    // the budgets available for that cost center — wait for that round trip before
    // interacting with the budget dropdown
    cy.intercept('POST', '**/company/cost-center/update-quote').as(
      'costCenterUpdateQuote'
    )
    // the native <select> is select2-enhanced and visually covered by select2's own
    // rendered UI, so a real click isn't needed (and isn't actionable) — force setting
    // the value directly still fires the native `change` event and the onchange handler
    this.getCostCenterSelect().select(costCenterName, { force: true })
    cy.wait('@costCenterUpdateQuote')
  }

  selectBudget = (budgetNameContains: string): void => {
    this.getBudgetSelect()
      .find('option')
      .contains(budgetNameContains)
      .then(($option) => {
        this.getBudgetSelect().select($option.text().trim(), { force: true })
      })
  }

  applyCostCenterAndBudget = (): void => {
    this.getCostCenterApplyButton().click()
  }

  submitSummaryForm = (): void => {
    cy.get(summaryForm).submit()
  }

  completeOrder = (): void => {
    this.getAcceptTermsAndConditionsCheckbox().click({ force: true })
    this.submitSummaryForm()
  }
}
