import { StorefrontSearchPage } from './storefront-search-page'

export class StorefrontSearchResultsPage extends StorefrontSearchPage {
  private getSearchResultsPage = (): Cypress.Chainable => {
    return cy.get('form[class*="main__catalog-page-content"]')
  }

  getFirstFoundProduct = (): Cypress.Chainable => {
    return this.getSearchResultsPage().find('product-item').first()
  }

  openSearchResults = (searchTerm: string): void => {
    this.getSearchField().type(searchTerm + '{enter}')
    this.getSearchResultsPage().should('be.visible')
  }

  findProduct = (searchTerm: string): void => {
    this.openSearchResults(searchTerm)
    this.getFirstFoundProduct().click()
  }
}
