import { AbstractPage } from '../../abstract-page'

export class StorefrontCustomerOverviewPage extends AbstractPage {
  protected PAGE_URL = Cypress.env('STOREFRONT_URL') + '/en/customer/overview'

  getOrdersTable = (): Cypress.Chainable => {
    return cy.get('[data-qa="component order-table"] > table')
  }

  getFirstOrderRow = (): Cypress.Chainable => {
    return this.getOrdersTable().find('tbody > tr').first()
  }

  getFirstOrderRowPrice = (): Cypress.Chainable => {
    return this.getFirstOrderRow()
      .find('[data-content="Total"] strong')
      .invoke('text')
  }

  getOrderRowActions = (rowIndex: number): Cypress.Chainable => {
    return this.getOrdersTable()
      .find('tbody > tr')
      .eq(rowIndex)
      .find('[data-qa="component table-action-list"]')
  }

  getOrderViewActionButton = (rowIndex: number): Cypress.Chainable => {
    return this.getOrderRowActions(rowIndex)
      .find('[data-qa="component table-action-link"] .table-action-link__title')
      .contains('View Order')
      .parent()
  }

  getFirstOrderViewActionButton = (): Cypress.Chainable => {
    return this.getOrderViewActionButton(0)
  }
}
