import checkoutData from '@fixtures/checkout-data.json'

import { AccessTokens } from '@support/glue-endpoints/authentication/access-tokens'
import { Carts } from '@support/glue-endpoints/cart/carts'
import { CartsItems } from '@support/glue-endpoints/cart/carts-items'
import { Checkout } from '@support/glue-endpoints/checkout/checkout'
import { validateSchema } from '@support/api-helper/api-helper'
import accessTokenSchema from '@support/glue-endpoints/authentication/access-tokens-response'
import checkoutSchema from '@support/glue-endpoints/checkout/checkout-response'
import {
  getFixtures,
  CustomerFixture,
  ProductFixture,
  ProductOfferFixture,
} from '@support/types/dynamic-fixtures'

interface GlueCheckoutDynamicFixtures {
  customer: CustomerFixture
  product: ProductFixture
  productOffer: ProductOfferFixture
}

interface GlueCheckoutStaticFixtures {
  defaultPassword: string
}

const tokenEndpoint = new AccessTokens()
const cartEndpoint = new Carts()
const itemsEndpoint = new CartsItems()
const checkoutEndpoint = new Checkout()

let dynamicFixtures: GlueCheckoutDynamicFixtures
let staticFixtures: GlueCheckoutStaticFixtures

before(() => {
  // the customer is created per run, so it starts with no carts or addresses to reset
  ;({ dynamicFixtures, staticFixtures } = getFixtures<
    GlueCheckoutDynamicFixtures,
    GlueCheckoutStaticFixtures
  >())
})

context('Customer checkout', () => {
  it('can place order via GLUE', () => {
    let token: string
    let cartId: string

    tokenEndpoint
      .getCustomerAccessToken(
        dynamicFixtures.customer.email,
        staticFixtures.defaultPassword
      )
      .then((response) => {
        validateSchema(accessTokenSchema, response)
        expect(response.isOkStatusCode).to.be.true
        token = response.body.data.attributes.accessToken
        return token
      })
      .then((token: string) => {
        return cartEndpoint.createGrossCart(token).then((response) => {
          expect(response.isOkStatusCode).to.be.true
          expect(response.body.data.id).to.not.be.null
          cartId = response.body.data.id
          return { token, cartId }
        })
      })
      .then(({ token, cartId }: { token: string; cartId: string }) => {
        itemsEndpoint
          .addOfferToCart(
            token,
            cartId,
            dynamicFixtures.product.sku,
            1,
            dynamicFixtures.productOffer.product_offer_reference,
            dynamicFixtures.productOffer.merchant_reference
          )
          .then((response) => {
            expect(response.isOkStatusCode).to.be.true
            return { token, cartId }
          })
      })
      .then(({ token, cartId }: { token: string; cartId: string }) => {
        checkoutEndpoint
          .placeOrder(
            token,
            cartId,
            dynamicFixtures.customer.email,
            checkoutData.glueShipment.id,
            checkoutData.gluePayment.providerName,
            checkoutData.gluePayment.methodName
          )
          .then((response) => {
            validateSchema(checkoutSchema, response)
            expect(response.isOkStatusCode).to.be.true
            expect(response.body.data.attributes.orderReference).to.be.not.null
            const orderReference = response.body.data.attributes.orderReference
            expect(response.body.included[0].id).to.be.eq(orderReference)
          })
      })
  })
})
