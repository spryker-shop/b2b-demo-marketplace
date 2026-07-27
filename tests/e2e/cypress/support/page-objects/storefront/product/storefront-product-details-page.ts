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

  addProductToCart = (): void => {
    this.getAddToCartButton().click()
    cy.get('flash-message').should('contain', 'Items added successfully')
    cy.closeAllFlashMessages()
  }
}
