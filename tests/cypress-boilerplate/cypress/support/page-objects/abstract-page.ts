export abstract class AbstractPage {
  protected abstract PAGE_URL: string
  visit = (options?: Partial<Cypress.VisitOptions>): void => {
    cy.visit(this.PAGE_URL, options)
  }
}
