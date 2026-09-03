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
    cy.intercept('POST', '**/company/cost-center/update-quote').as(
      'costCenterUpdateQuote'
    )
    this.getCostCenterSelect().then(($select) => {
      if ($select.find('option:selected').text().trim() === costCenterName) {
        return
      }

      cy.wrap($select).select(costCenterName, { force: true })
      cy.wait('@costCenterUpdateQuote')
    })
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
