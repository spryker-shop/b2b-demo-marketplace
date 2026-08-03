import { AbstractPage } from '../../abstract-page'

export class StorefrontCheckoutSuccessPage extends AbstractPage {
  protected PAGE_URL = Cypress.env('STOREFRONT_URL') + '/en/checkout/success'

  checkOrderSuccess = (): void => {
    cy.location('pathname', { timeout: 30000 }).should(
      'match',
      /^\/([^\/]+\/)?en\/checkout\/success$/
    )
  }

  getOrderReference = (): Cypress.Chainable<string> => {
    return cy.get('meta[itemprop="identifier"]').invoke('attr', 'content')
  }
}
