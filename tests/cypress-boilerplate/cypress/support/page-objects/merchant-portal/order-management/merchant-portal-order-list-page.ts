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

  getOrderSearchInput = (): Cypress.Chainable => {
    return cy.get('input[placeholder="Search"]', { timeout: 20000 })
  }

  /**
   * Narrows the merchant order table down to a single order. The table is paginated and not
   * sorted by recency, so a freshly placed order is usually not on the first page — without
   * searching first, looking it up in the table only ever finds it by luck.
   */
  searchOrderByReference = (orderReference: string): Cypress.Chainable => {
    cy.intercept('GET', '**/sales-merchant-portal-gui/orders/table-data**').as(
      'merchantOrdersTableData'
    )
    this.getOrderSearchInput().clear().type(`${orderReference}{enter}`)
    cy.wait('@merchantOrdersTableData', { timeout: 30000 })

    return cy.get('mp-sales-orders-table', { timeout: 20000 })
  }

  viewOrderByReference = (orderReference: string, maxRetries = 5): void => {
    const tryFind = (retries = 0): void => {
      this.searchOrderByReference(orderReference)
      this.getOrderInTableByReference(orderReference).then(($rows) => {
        if ($rows.length > 0) {
          this.getOrderInTableByReference(orderReference)
            .should('have.length', 1)
            .click({ force: true })
          cy.get('web-mp-manage-order', { timeout: 20000 }).should('be.visible')
          return
        }

        if (retries >= maxRetries) {
          throw new Error(
            `Order "${orderReference}" not found in the merchant order table after ${maxRetries} reload(s) — the order may not have finished syncing to the merchant portal yet.`
          )
        }

        cy.log(
          `Order "${orderReference}" not found yet. Reloading... [${retries + 1}/${maxRetries}]`
        )
        cy.reload()
        cy.wait(5000)
        tryFind(retries + 1)
      })
    }

    tryFind()
  }
}
