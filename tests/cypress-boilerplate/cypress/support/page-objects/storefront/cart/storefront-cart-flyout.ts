import { AbstractPage } from '../../abstract-page'

export class StorefrontCartFlyout extends AbstractPage {
  protected PAGE_URL = ''

  getCartBadge = (): Cypress.Chainable => {
    return cy.get('[data-qa="component header-cart-pill"] .cart-trigger__badge')
  }

  getCartTrigger = (): Cypress.Chainable => {
    return cy.get(
      '[data-qa="component header-cart-pill"] .js-header-cart-pill__trigger'
    )
  }
}
