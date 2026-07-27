import { AbstractPage } from '../../abstract-page'

export class MerchantOrderListPage extends AbstractPage {
  protected PAGE_URL =
    Cypress.env('MP_URL') + '/sales-merchant-portal-gui/orders'

  getOrderInTableByPosition = (orderPosition: number): Cypress.Chainable => {
    return cy
      .get('mp-sales-orders-table')
      .find('tbody')
      .find('tr')
      .eq(orderPosition)
  }

  getOrderInTableByReference = (orderReference: string): Cypress.Chainable => {
    return cy
      .get('mp-sales-orders-table')
      .find('tbody')
      .find('tr')
      .filter((_, row) => {
        return Cypress.$(row).find('td').eq(0).text().trim() === orderReference
      })
  }

  getOrderReference = (orderPosition: number): Cypress.Chainable => {
    return this.getOrderInTableByPosition(orderPosition).find('td').eq(0)
  }

  viewOrderByPosition = (orderPosition: number): void => {
    this.getOrderInTableByPosition(orderPosition).click()
    cy.get('web-mp-manage-order', { timeout: 20000 }).should('be.visible')
  }
}
