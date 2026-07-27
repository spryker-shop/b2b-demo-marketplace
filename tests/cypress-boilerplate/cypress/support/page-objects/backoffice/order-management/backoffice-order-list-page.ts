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

  getOrderInTableByReference = (orderReference: string): Cypress.Chainable => {
    return cy
      .get('[data-qa="data-table"]')
      .find('tbody')
      .find('tr')
      .contains(orderReference)
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
    this.getOrderViewButtonByReference(orderReference).click()
  }
}
