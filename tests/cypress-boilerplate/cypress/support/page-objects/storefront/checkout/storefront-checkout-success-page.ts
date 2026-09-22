import { AbstractPage } from '../../abstract-page'

export class StorefrontCheckoutSuccessPage extends AbstractPage {
  protected PAGE_URL =
    Cypress.env('STOREFRONT_URL') +
    '/' +
    Cypress.env('LOCALE_PREFIX') +
    '/checkout/success'

  checkOrderSuccess = (): void => {
    const localePrefix = Cypress.env('LOCALE_PREFIX')

    cy.location('pathname', { timeout: 30000 }).should(
      'match',
      new RegExp(`^/([^/]+/)?${localePrefix}/checkout/success$`)
    )
  }

  getOrderReference = (): Cypress.Chainable<string> => {
    return cy.get('meta[itemprop="identifier"]').invoke('attr', 'content')
  }
}
