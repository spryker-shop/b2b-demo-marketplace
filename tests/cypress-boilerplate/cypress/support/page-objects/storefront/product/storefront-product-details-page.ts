import { AbstractPage } from '../../abstract-page'

export class StorefrontProductDetailsPage extends AbstractPage {
  protected PAGE_URL = ''

  getProductName = (): Cypress.Chainable => {
    return cy.get('[itemprop="name"]')
  }

  getProductPrice = (): Cypress.Chainable => {
    return cy.get('[itemprop="price"]')
  }

  getAddToCartButton = (): Cypress.Chainable => {
    return cy.get('[data-qa="add-to-cart-button"]')
  }

  getQtyIncreaseButton = (): Cypress.Chainable => {
    return cy.get('.quantity-counter__button--increment')
  }

  getQtyDecreaseButton = (): Cypress.Chainable => {
    return cy.get('.quantity-counter__button--decrement"')
  }

  getAddToCartQuantityInput = (): Cypress.Chainable => {
    return this.getAddToCartButton()
      .closest('form')
      .find('[data-qa="quantity-input"]')
  }

  increaseAddToCartQuantity = (): void => {
    this.getAddToCartButton()
      .closest('form')
      .find('[data-qa="quantity-counter__button--increment"]')
      .click()
  }

  addProductToCart = (): void => {
    this.getAddToCartButton().click()
    cy.get('flash-message.flash-message--success').should('exist')
    cy.closeAllFlashMessages()
  }
}
