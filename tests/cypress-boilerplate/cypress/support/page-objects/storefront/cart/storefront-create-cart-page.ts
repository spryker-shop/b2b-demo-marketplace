import { AbstractPage } from '../../abstract-page'

const quoteForm = 'form[name="quoteForm"]'

export class StorefrontCreateCartPage extends AbstractPage {
  protected PAGE_URL =
    Cypress.env('STOREFRONT_URL') +
    '/' +
    Cypress.env('LOCALE_PREFIX') +
    '/multi-cart/create'

  getCartNameField = (): Cypress.Chainable => {
    return cy.get('#quoteForm_name')
  }

  createCart = (): Cypress.Chainable => {
    return cy.get(quoteForm).submit()
  }
}
