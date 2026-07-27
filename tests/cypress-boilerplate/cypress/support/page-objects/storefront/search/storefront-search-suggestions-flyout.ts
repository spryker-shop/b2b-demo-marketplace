import { StorefrontSearchPage } from './storefront-search-page'

export class StorefrontSearchSuggestionsFlyout extends StorefrontSearchPage {
  getSearchSuggestionFlyout = (): Cypress.Chainable => {
    return cy.get('suggest-search')
  }

  getFirstSuggestedProductLink = (): Cypress.Chainable => {
    return this.getSearchSuggestionFlyout().find('a').first()
  }

  findProduct = (searchTerm: string): void => {
    this.getSearchField().type(searchTerm)
    this.getFirstSuggestedProductLink().click()
  }
}
