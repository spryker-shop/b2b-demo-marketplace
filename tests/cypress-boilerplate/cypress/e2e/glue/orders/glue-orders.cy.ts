import checkoutData from '@fixtures/checkout-data.json'

import { AccessTokens } from '@support/glue-endpoints/authentication/access-tokens'
import { Orders } from '@support/glue-endpoints/order/orders'
import { validateSchema } from '@support/api-helper/api-helper'
import { GlueCheckoutScenarios } from '@support/scenarios/glue/glue-checkout-scenarios'
import ordersSchema from '@support/glue-endpoints/order/orders-response'
import {
  getFixtures,
  CustomerFixture,
  ProductFixture,
  ProductOfferFixture,
} from '@support/types/dynamic-fixtures'

interface GlueOrdersDynamicFixtures {
  customer: CustomerFixture
  product: ProductFixture
  productOffer: ProductOfferFixture
}

interface GlueOrdersStaticFixtures {
  defaultPassword: string
}

const tokenEndpoint = new AccessTokens()
const ordersEndpoint = new Orders()
const glueCheckoutScenarios = new GlueCheckoutScenarios()

let dynamicFixtures: GlueOrdersDynamicFixtures
let staticFixtures: GlueOrdersStaticFixtures
let createdOrderReference: string

before(() => {
  // the customer is created per run, so it starts with no carts or addresses to reset
  ;({ dynamicFixtures, staticFixtures } = getFixtures<
    GlueOrdersDynamicFixtures,
    GlueOrdersStaticFixtures
  >())

  // place an order for retrieving order details
  glueCheckoutScenarios
    .placeOrder(
      dynamicFixtures.customer.email,
      staticFixtures.defaultPassword,
      dynamicFixtures.product.sku,
      checkoutData.glueShipment.id,
      checkoutData.gluePayment.providerName,
      checkoutData.gluePayment.methodName,
      dynamicFixtures.productOffer.product_offer_reference,
      dynamicFixtures.productOffer.merchant_reference
    )
    .then(({ orderReference }) => {
      createdOrderReference = orderReference
    })
})

context('Customer orders', () => {
  it('can retrieve order via GLUE', () => {
    tokenEndpoint
      .getCustomerAccessToken(
        dynamicFixtures.customer.email,
        staticFixtures.defaultPassword
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
        dynamicFixtures.customer.email,
        staticFixtures.defaultPassword
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
