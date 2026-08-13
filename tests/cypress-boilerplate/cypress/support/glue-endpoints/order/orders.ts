import { GlueRequest } from '../glue-request'

export class Orders extends GlueRequest {
  protected ENDPOINT_NAME = `/orders`

  getCustomerOrder = (
    token: string,
    orderReference: string,
    failOnStatusCode = true
  ): Cypress.Chainable => {
    return cy.api({
      method: 'GET',
      url: `${this.ENDPOINT_NAME}/${orderReference}`,
      headers: {
        'Content-Type': 'application/json',
        Authorization: 'Bearer ' + token,
      },
      failOnStatusCode: failOnStatusCode,
    })
  }
}
