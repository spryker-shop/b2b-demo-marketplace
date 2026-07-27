import { AccessTokens } from 'cypress/support/glue-endpoints/authentication/access-tokens'
import { Carts } from 'cypress/support/glue-endpoints/cart/carts'
import { CartsItems } from 'cypress/support/glue-endpoints/cart/carts-items'
import { Checkout } from 'cypress/support/glue-endpoints/checkout/checkout'

const tokenEndpoint = new AccessTokens()
const cartEndpoint = new Carts()
const itemsEndpoint = new CartsItems()
const checkoutEndpoint = new Checkout()

export class GlueCheckoutScenarios {
  /**
   * Places an order via the Glue API.
   *
   * @example
   * const glueCheckoutScenarios = new GlueCheckoutScenarios()
   * glueCheckoutScenarios.placeOrder(
   *   'sonia@spryker.com',
   *   'change123',
   *   '464012',
   *   1,
   *   'Dummy Payment',
   *   'Invoice',
   *   'offer123',
   *   'MER0001'
   * ).then((orderReference) => {
   *   cy.log('Order placed with reference: ' + orderReference)
   * })
   */
  placeOrder = (
    email: string,
    password: string,
    sku: string,
    shipment: number,
    paymentProvider: string,
    paymentMethod: string,
    offer: string,
    merchant: string
  ): Cypress.Chainable<{
    orderReference: string
    orderDetails: object | null
  }> => {
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
              const orderReference =
                response.body.data.attributes.orderReference
              const orderDetails = response.body.included.find(
                (item) => item.type === 'orders' && item.id === orderReference
              )?.attributes

              return { orderReference, orderDetails }
            })
        })
    })
  }
}
