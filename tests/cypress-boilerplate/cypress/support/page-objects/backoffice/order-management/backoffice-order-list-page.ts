import { AbstractPage } from '../../abstract-page'

export class BackofficeOrderListPage extends AbstractPage {
  protected PAGE_URL = Cypress.env('BACK_OFFICE_URL') + '/sales'

  getOrderInTableByPosition = (orderPosition: number): Cypress.Chainable => {
    return cy
      .get('[data-qa="data-table"]')
      .find('tbody')
      .find('tr')
      .eq(orderPosition)
  }

  getOrderSearchInput = (): Cypress.Chainable => {
    return cy
      .get('[data-qa="data-table"]')
      .closest('.dt-container')
      .find('.dt-search input[type="search"]')
  }

  filterOrdersByReference = (orderReference: string): Cypress.Chainable => {
    this.getOrderSearchInput().clear().type(orderReference)
    return cy
      .get('[data-qa="data-table"]')
      .find('tbody')
      .find('tr')
      .should('contain', orderReference, { timeout: 10000 })
  }

  getOrderInTableByReference = (orderReference: string): Cypress.Chainable => {
    return cy
      .get('[data-qa="data-table"]')
      .find('tbody')
      .find('tr')
      .contains(orderReference, { timeout: 10000 })
  }

  getOrderReference = (orderPosition: number): Cypress.Chainable => {
    return this.getOrderInTableByPosition(orderPosition).find('td').eq(1)
  }

  getOrderViewButtonByPosition = (orderPosition: number): Cypress.Chainable => {
    return this.getOrderInTableByPosition(orderPosition).contains('View')
  }

  getOrderViewButtonByReference = (
    orderReference: string
  ): Cypress.Chainable => {
    return this.getOrderInTableByReference(orderReference)
      .siblings()
      .contains('View')
  }

  viewOrderByPosition = (orderPosition: number): void => {
    this.getOrderViewButtonByPosition(orderPosition).click()
  }

  viewOrderByReference = (orderReference: string): void => {
    this.filterOrdersByReference(orderReference)
    this.getOrderViewButtonByReference(orderReference).click()
  }
}
