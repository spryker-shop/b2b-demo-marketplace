declare module 'cypress-mochawesome-reporter/register'

declare namespace Cypress {
  interface Chainable {
    /**
     * @example cy.closeAllFlashMessages()
     */
    closeAllFlashMessages(): Cypress.Chainable<any>

    /**
     * @example cy.createNewCart()
     */
    createNewCart(): Cypress.Chainable<any>

    /**
     * @example cy.placeOrderViaGlue('sonia@spryker.com','change123','464012',1, 'Dummy Payment', 'Invoice', 'offer123', 'MER0001')
     */
    placeOrderViaGlue(
      email: string,
      password: string,
      sku: string,
      shipment: number,
      paymentProvider: string,
      paymentMethod: string,
      offer: string,
      merchant: string
    ): Cypress.Chainable<string>

    /**
     * * @example cy.triggerOmsTransition('/path/to/spryker/env')
     */
    triggerOmsTransition(path?: string): Cypress.Chainable<any>

    /**
     * * @example cy.deleteAllCustomerAddresses('sonia@spryker.com','change123','DE--21')
     */
    deleteAllCustomerAddresses(
      email: string,
      password: string,
      customerReference: string
    ): Cypress.Chainable<any>

    /**
     * * @example cy.deleteAllShoppingCarts('sonia@spryker.com','change123')
     */
    deleteAllShoppingCarts(
      email: string,
      password: string
    ): Cypress.Chainable<any>

    /**
     * * @example cy.triggerOmsEvent('DE--1', 'Pay')
     */
    triggerOmsEvent(
      orderReference: string,
      eventName: string
    ): Cypress.Chainable<any>

    /**
     * * @example cy.waitForOrderProcessing('sent to merchant', 20)
     */
    waitForOrderProcessing(
      desiredStatus: string,
      maxRetries: number
    ): Cypress.Chainable<any>

    /**
     * @example cy.formatDisplayPrice(8999)
     */
    formatDisplayPrice(price: number): Cypress.Chainable<string>
  }
}
