import { GlueRequest } from '../glue-request'

export class AccessTokens extends GlueRequest {
  protected ENDPOINT_NAME = '/access-tokens'

  getCustomerAccessToken = (
    email: string,
    password: string
  ): Cypress.Chainable => {
    return cy.api({
      method: 'POST',
      url: this.GLUE_ENDPOINT,
      headers: {
        'Accept-Language': 'de-DE, de;q=0.9',
      },
      body: {
        data: {
          type: 'access-tokens',
          attributes: {
            username: email,
            password: password,
          },
        },
      },
      failOnStatusCode: false,
    })
  }
}
