import { AbstractPage } from '../../abstract-page'

export class StorefrontSearchPage extends AbstractPage {
  protected PAGE_URL =
    Cypress.env('STOREFRONT_URL') +
    '/' +
    Cypress.env('LOCALE_PREFIX') +
    '/search'

  getSearchField = (): Cypress.Chainable => {
    return cy.get('header').find('input[name="q"]')
  }
}
