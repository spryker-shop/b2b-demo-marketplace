import { AccessTokens } from 'cypress/support/glue-endpoints/authentication/access-tokens'
import { Carts } from 'cypress/support/glue-endpoints/cart/carts'
import { CartsItems } from 'cypress/support/glue-endpoints/cart/carts-items'
import { Checkout } from 'cypress/support/glue-endpoints/checkout/checkout'

const tokenEndpoint = new AccessTokens()
const cartEndpoint = new Carts()
const itemsEndpoint = new CartsItems()
const checkoutEndpoint = new Checkout()

Cypress.Commands.add(
  'placeOrderViaGlue',
  (
    email: string,
    password: string,
    sku: string,
    shipment: number,
    paymentProvider: string,
    paymentMethod: string,
    offer: string,
    merchant: string
  ): Cypress.Chainable<string> => {
    let token: string
    let cartId: string

    return cy.wrap(null).then(() => {
      return tokenEndpoint
        .getCustomerAccessToken(email, password)
        .then((response) => {
          token = response.body.data.attributes.accessToken
          return token
        })
        .then((token) => {
          return cartEndpoint.createGrossCart(token).then((response) => {
            cartId = response.body.data.id
            return { token, cartId }
          })
        })
        .then(({ token, cartId }) => {
          return itemsEndpoint
            .addOfferToCart(token, cartId, sku, 1, offer, merchant)
            .then(() => {
              return { token, cartId }
            })
        })
        .then(({ token, cartId }) => {
          return checkoutEndpoint
            .placeOrder(
              token,
              cartId,
              email,
              shipment,
              paymentProvider,
              paymentMethod
            )
            .then((response) => {
              return response.body.data.attributes.orderReference
            })
        })
    })
  }
)
