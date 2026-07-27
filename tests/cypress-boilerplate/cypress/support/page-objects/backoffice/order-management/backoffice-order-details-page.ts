import { AbstractPage } from '../../abstract-page'

const omsTriggerForm = 'form[name="oms_trigger_form"]'

export class BackofficeOrderDetailsPage extends AbstractPage {
  protected PAGE_URL = ''

  openOrderDetails = (orderId: number): Cypress.Chainable => {
    this.PAGE_URL =
      Cypress.env('BACK_OFFICE_URL') + '/sales/detail?id-sales-order=' + orderId
    return cy.visit(this.PAGE_URL)
  }

  private getOrderOverview = (): Cypress.Chainable => {
    return cy.get('#order-overview')
  }

  getOrderGrandTotals = (): Cypress.Chainable => {
    return this.getOrderOverview().find('.grandTotal-row')
  }

  getOrderSubtotal = (): Cypress.Chainable => {
    return this.getOrderOverview().find('.subtotal-row')
  }

  getOmsTriggers = (): Cypress.Chainable => {
    return cy.get(omsTriggerForm)
  }

  private getOrderFlashMessages = (): Cypress.Chainable => {
    return cy.get('.flash-messages')
  }

  getSuccessfulOrderMessages = (): Cypress.Chainable => {
    return this.getOrderFlashMessages().find('.alert-info')
  }

  triggerOms = (triggerName: string): void => {
    this.getOmsTriggers()
      .contains(triggerName, { timeout: 10000 })
      .should('be.visible')
      .click()
  }

  private getOrderItems = (): Cypress.Chainable => {
    return cy.get('[data-qa="order-item-list"]')
  }

  getOrderItemBySku = (concreteSku: string): Cypress.Chainable => {
    return this.getOrderItems().contains('.sku', concreteSku).parents('tr')
  }

  getOrderItemHistory = (concreteSku: string): Cypress.Chainable => {
    return this.getOrderItemBySku(concreteSku).find('.state-history')
  }

  getOrderItemTotal = (concreteSku: string): Cypress.Chainable => {
    return this.getOrderItemBySku(concreteSku).find(
      '[data-qa="item-total-amount"]'
    )
  }

  getCustomerEmail = (): Cypress.Chainable => {
    return cy.get('#customer dt:contains("Email") + dd')
  }
}
