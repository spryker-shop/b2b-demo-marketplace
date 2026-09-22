import { AccessTokens } from '../../glue-endpoints/authentication/access-tokens'
import { Carts } from '../../glue-endpoints/cart/carts'

const tokenEndpoint = new AccessTokens()
const cartEndpoint = new Carts()

Cypress.Commands.add(
  'deleteAllShoppingCarts',
  (email: string, password: string): Cypress.Chainable => {
    let token: string

    return cy.wrap(null).then(() => {
      return tokenEndpoint
        .getCustomerAccessToken(email, password)
        .then((response) => {
          token = response.body.data.attributes.accessToken
          return token
        })
        .then((token) => {
          // Step 1: Create a new shopping cart
          return cartEndpoint.createGrossCart(token).then((createResponse) => {
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
                        }
                        expect(deleteResponse.status).to.eq(204)
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
)
