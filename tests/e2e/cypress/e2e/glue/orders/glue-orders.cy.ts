// this spec places an order directly via the Glue API, so it needs a customer whose
// business unit has no B2B purchasing restrictions (no cost-center/budget requirement) —
// that feature has no Glue API support in this project and would block checkout with a
// 422 no matter what payload is sent. The shared customer-data.json fixture
// (Sonia / Acme Corporation) has this restriction.
import customerCredentials from '@fixtures/customer-order-data.json'
import productData from '@fixtures/product-data.json'
import checkoutData from '@fixtures/checkout-data.json'

import { AccessTokens } from '@support/glue-endpoints/authentication/access-tokens'
import { Orders } from '@support/glue-endpoints/order/orders'
import { validateSchema } from '@support/api-helper/api-helper'
import { GlueAddressesScenarios } from '@support/scenarios/glue/glue-addresses-scenarios'
import { GlueCartsScenarios } from '@support/scenarios/glue/glue-carts-scenarios'
import { GlueCheckoutScenarios } from '@support/scenarios/glue/glue-checkout-scenarios'
import ordersSchema from '@support/glue-endpoints/order/orders-response'

const tokenEndpoint = new AccessTokens()
const ordersEndpoint = new Orders()
const glueAddressesScenarios = new GlueAddressesScenarios()
const glueCartsScenarios = new GlueCartsScenarios()
const glueCheckoutScenarios = new GlueCheckoutScenarios()

let createdOrderReference: string

before(() => {
  // reset customer addresses
  glueAddressesScenarios.deleteAllCustomerAddresses(
    customerCredentials.email,
    customerCredentials.password,
    customerCredentials.reference
  )
  // reset customer carts
  glueCartsScenarios.deleteAllShoppingCarts(
    customerCredentials.email,
    customerCredentials.password
  )

  // place an order for retrieving order details
  glueCheckoutScenarios
    .placeOrder(
      customerCredentials.email,
      customerCredentials.password,
      productData.availableOffer.concreteSku,
      checkoutData.glueShipment.id,
      checkoutData.gluePayment.providerName,
      checkoutData.gluePayment.methodName,
      productData.availableOffer.offer,
      productData.availableOffer.merchantReference
    )
    .then(({ orderReference }) => {
      createdOrderReference = orderReference
    })
})

context('Customer orders', () => {
  it('can retrieve order via GLUE', () => {
    tokenEndpoint
      .getCustomerAccessToken(
        customerCredentials.email,
        customerCredentials.password
      )
      .then((response) => {
        expect(response.isOkStatusCode).to.be.true

        return response.body.data.attributes.accessToken
      })
      .then((token: string) => {
        return ordersEndpoint
          .getCustomerOrder(token, createdOrderReference)
          .then((response) => {
            // validating response structure according to schema
            validateSchema(ordersSchema, response)
            expect(response.isOkStatusCode).to.be.true
            // asserting order reference is returned
            expect(response.body.data.id).to.be.eq(createdOrderReference)
          })
      })
  })

  it('unauthorized order request fails via GLUE', () => {
    ordersEndpoint
      .getCustomerOrder('', createdOrderReference, false)
      .then((response) => {
        expect(response.status).to.be.eq(401)
      })
  })

  it('missing order returns error via GLUE', () => {
    tokenEndpoint
      .getCustomerAccessToken(
        customerCredentials.email,
        customerCredentials.password
      )
      .then((response) => {
        expect(response.isOkStatusCode).to.be.true

        return response.body.data.attributes.accessToken
      })
      .then((token: string) => {
        // requesting order that does not exist
        return ordersEndpoint
          .getCustomerOrder(token, 'missing-order', false)
          .then((response) => {
            expect(response.status).to.be.eq(404)
          })
      })
  })
})
