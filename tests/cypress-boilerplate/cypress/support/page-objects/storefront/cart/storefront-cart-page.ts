import { AbstractPage } from '../../abstract-page'

export class StorefrontCartPage extends AbstractPage {
  protected PAGE_URL =
    Cypress.env('STOREFRONT_URL') + '/' + Cypress.env('LOCALE_PREFIX') + '/cart'

  getCartItemsList = (): Cypress.Chainable => {
    // wait longer for the cart items list to appear after add-to-cart navigation or async rendering
    return cy.get('cart-items-list', { timeout: 20000 })
  }

  getCartItem = (concreteSku: string): Cypress.Chainable => {
    return this.getCartItemsList()
      .contains('span[itemprop="sku"]', concreteSku)
      .parents('[data-qa="component product-cart-item"]')
  }

  getCartItemPrice = (concreteSku: string): Cypress.Chainable => {
    return this.getCartItem(concreteSku).find('[itemprop="price"]')
  }

  getCheckoutButton = (): Cypress.Chainable => {
    return cy.get('[data-qa="cart-go-to-checkout"]')
  }
}
