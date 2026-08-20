import { AbstractPage } from '../../abstract-page'

export class StorefrontCustomerOrderDetailsPage extends AbstractPage {
  protected PAGE_URL = ''

  getPageTitle = (): Cypress.Chainable => {
    return cy.get('[data-qa="component action-bar"] h3')
  }

  getOrderSummary = (): Cypress.Chainable => {
    return cy.get('[data-qa="component order-summary"]')
  }

  getOrderSummaryGrandTotal = (): Cypress.Chainable => {
    return this.getOrderSummary()
      .find('.order-summary__item--total')
      .find('strong.order-summary__title--total')
      .filter(':visible')
      .invoke('text')
  }

  getOrderInfoBlock = (): Cypress.Chainable => {
    return cy.get('[data-qa="component order-info"]')
  }

  getOrderInfoBlockOrderReference = (): Cypress.Chainable => {
    return this.getOrderInfoBlock()
      .find('.order-info__item > span')
      .eq(1)
      .invoke('text')
  }
}
