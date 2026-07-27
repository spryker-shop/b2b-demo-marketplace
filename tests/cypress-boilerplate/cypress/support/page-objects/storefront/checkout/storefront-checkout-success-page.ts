import { AbstractPage } from '../../abstract-page'

export class StorefrontCheckoutSuccessPage extends AbstractPage {
  protected PAGE_URL = Cypress.env('STOREFRONT_URL') + '/en/checkout/success'

  checkOrderSuccess = (): void => {
    // this checkout redirects through an intermediate /checkout/place-order processing
    // step before landing on /checkout/success, so give it more than the default 4s
    // Allow optional single path segment (e.g. /DE/en/checkout/success or /DE-AT/en/checkout/success)
    cy.location('pathname', { timeout: 30000 }).should(
      'match',
      /^\/([^\/]+\/)?en\/checkout\/success$/
    )
  }

  getOrderReference = (): Cypress.Chainable<string> => {
    return cy.get('meta[itemprop="identifier"]').invoke('attr', 'content')
  }
}
