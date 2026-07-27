import { StorefrontCreateCartPage } from '../../page-objects/storefront/cart/storefront-create-cart-page'

export class StorefrontCartScenarios {
  storefrontCreateCartPage = new StorefrontCreateCartPage()

  /**
   * Creates a new cart with the current date and time as the cart name.
   *
   * @example
   * const storefrontCartScenarios = new StorefrontCartScenarios()
   * storefrontCartScenarios.createNewCart()
   *   .then(() => {
   *     // Further commands can be chained here
   *   })
   *
   * @returns {Cypress.Chainable} A chainable object representing the result of the cart creation process.
   */
  createNewCart = (): Cypress.Chainable => {
    const currentDateTime = new Date()

    return cy.wrap(null).then(() => {
      this.storefrontCreateCartPage.visit()
      this.storefrontCreateCartPage
        .getCartNameField()
        .clear()
        .type(currentDateTime.toISOString())

      return this.storefrontCreateCartPage.createCart()
    })
  }
}
