import { GlueRequest } from '../glue-request'

export class Carts extends GlueRequest {
  protected ENDPOINT_NAME = '/carts'

  createGrossCart = (token: string): Cypress.Chainable => {
    const currentDateTime = new Date()
    return cy.api({
      method: 'POST',
      url: this.GLUE_ENDPOINT,
      headers: {
        Authorization: 'Bearer ' + token,
      },
      body: {
        data: {
          type: 'carts',
          attributes: {
            name: currentDateTime.toISOString(),
            priceMode: 'GROSS_MODE',
            currency: 'EUR',
            store: 'DE',
          },
        },
      },
      failOnStatusCode: false,
    })
  }
}
