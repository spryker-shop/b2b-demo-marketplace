import { AccessTokens } from '../../glue-endpoints/authentication/access-tokens'
import { Carts } from '../../glue-endpoints/cart/carts'

export class GlueCartsScenarios {
  tokenEndpoint = new AccessTokens()
  cartEndpoint = new Carts()

  /**
   * Deletes all shopping carts except a newly created one via the Glue API.
   *
   * @example
   * const glueCartsScenarios = new GlueCartsScenarios()
   * glueCartsScenarios.deleteAllShoppingCarts('sonia@spryker.com', 'change123')
   *   .then(() => {
   *     cy.log('All old shopping carts deleted, except the new one.')
   *   })
   */
  deleteAllShoppingCarts = (
    email: string,
    password: string
  ): Cypress.Chainable => {
    let token: string

    return cy.wrap(null).then(() => {
      return this.tokenEndpoint
        .getCustomerAccessToken(email, password)
        .then((response) => {
          token = response.body.data.attributes.accessToken
          return token
        })
        .then((token) => {
          // Step 1: Create a new shopping cart
          return this.cartEndpoint
            .createGrossCart(token)
            .then((createResponse) => {
              expect(createResponse.status).to.eq(201)
              const newCartId = createResponse.body.data.id
              cy.log(`New shopping cart created with ID: ${newCartId}`)

              // Step 2: Get the list of all shopping carts
              return cy
                .api({
                  method: 'GET',
                  url: `${Cypress.env('GLUE_URL')}/carts`,
                  headers: {
                    Authorization: `Bearer ${token}`,
                  },
                  failOnStatusCode: false,
                })
                .then((getCartsResponse) => {
                  expect(getCartsResponse.status).to.eq(200)
                  const carts = getCartsResponse.body.data
                  cy.log(`${carts.length} carts found.`)

                  // Step 3: Delete all carts except the newly created one
                  let deleteCount = 0

                  const deletePromises = carts.map((cart) => {
                    if (cart.id !== newCartId) {
                      return cy
                        .api({
                          method: 'DELETE',
                          url: `${Cypress.env('GLUE_URL')}/carts/${cart.id}`,
                          headers: {
                            Authorization: `Bearer ${token}`,
                          },
                          failOnStatusCode: false,
                        })
                        .then((deleteResponse) => {
                          if (deleteResponse.status === 204) {
                            deleteCount++
                          } else {
                            // some carts can't be deleted via this endpoint (e.g. a
                            // customer's last/default cart, or a cost-center/budget
                            // restricted cart in this project's Purchasing Control
                            // feature) — that's an expected business rule, not a
                            // cleanup failure, so don't fail the whole suite over it
                            cy.log(
                              `Cart ${cart.id} could not be deleted (status ${deleteResponse.status}), leaving it in place.`
                            )
                          }
                        })
                    } else {
                      return Cypress.Promise.resolve()
                    }
                  })

                  return Cypress.Promise.all(deletePromises).then(() => {
                    cy.log(`${deleteCount} old carts were deleted.`)
                  })
                })
            })
        })
    })
  }
}
