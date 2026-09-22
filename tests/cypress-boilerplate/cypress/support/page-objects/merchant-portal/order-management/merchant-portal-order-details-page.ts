import { AbstractPage } from '../../abstract-page'

const orderSidePanel = 'spy-drawer-wrapper'

export class MerchantOrderDetailsPage extends AbstractPage {
  //this page can only be opened by clicking order from order list page, i.e. using orders.viewOrderByPosition(orderPosition) of MerchantOrderListPage class
  protected PAGE_URL = ''

  private getOrderTotals = (): Cypress.Chainable => {
    return cy.get(orderSidePanel).find('.mp-manage-order-totals')
  }

  getOrderSubTotals = (): Cypress.Chainable => {
    return this.getOrderTotals().eq(0)
  }

  getOrderGrandTotals = (): Cypress.Chainable => {
    return this.getOrderTotals().eq(1)
  }

  openOrderTab = (tabName: string): void => {
    cy.get('nz-tabs-nav').contains(tabName).click()
  }

  getOmsTriggers = (): Cypress.Chainable => {
    return cy.get('.mp-manage-order__transitions')
  }

  triggerOms = (triggerName: string): void => {
    this.getOmsTriggers().contains(triggerName).click()
    cy.get('.ant-alert-success').should('be.visible')
    cy.get('.ant-alert-success').click()
    cy.get('web-mp-manage-order', { timeout: 20000 }).should('be.visible')
  }

  private getOrderItems = (): Cypress.Chainable => {
    return cy.get(orderSidePanel).find('tbody')
  }

  getOrderItemBySku = (concreteSku: string): Cypress.Chainable => {
    return this.getOrderItems().contains(concreteSku).parents('tr')
  }

  getOrderItemState = (concreteSku: string): Cypress.Chainable => {
    return this.getOrderItemBySku(concreteSku).find('td').eq(5)
  }

  getOrderItemsStates = (): Cypress.Chainable => {
    return cy.get(orderSidePanel).find('.mp-manage-order__states')
  }
}
