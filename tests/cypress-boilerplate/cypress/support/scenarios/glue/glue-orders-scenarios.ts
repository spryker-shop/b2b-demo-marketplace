import { AccessTokens } from 'cypress/support/glue-endpoints/authentication/access-tokens'
import { Orders } from '@support/glue-endpoints/order/orders'

const tokenEndpoint = new AccessTokens()
const ordersEndpoint = new Orders()

export class GlueOrdersScenarios {
  /**
   * Retrieves an order via the Glue API.
   *
   * @example
   * const glueOrdersScenarios = new GlueOrdersScenarios()
   * glueOrdersScenarios.getCustomerOrder(
   *   'sonia@spryker.com`
   *   'change123',
   *   '00000001'
   *   ).then((order) => {
   *   cy.log('Order retrieved: ' + order)
   *   })
   */
  getCustomerOrder = (
    email: string,
    password: string,
    orderReference: string
  ): Cypress.Chainable<object> => {
    let token: string

    return cy.wrap(null).then(() => {
      return tokenEndpoint
        .getCustomerAccessToken(email, password)
        .then((response) => {
          token = response.body.data.attributes.accessToken
          return token
        })
        .then((token) => {
          return ordersEndpoint
            .getCustomerOrder(token, orderReference)
            .then((response) => {
              return response.body.data.attributes
            })
        })
    })
  }
}
