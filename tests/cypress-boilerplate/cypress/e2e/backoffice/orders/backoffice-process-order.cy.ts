import checkoutData from '@fixtures/checkout-data.json'
import userCredentials from '@fixtures/user-data.json'
import { BackofficeLoginPage } from '@support/page-objects/backoffice/login/backoffice-login-page'
import { BackofficeOrderListPage } from '@support/page-objects/backoffice/order-management/backoffice-order-list-page'
import { BackofficeOrderDetailsPage } from '@support/page-objects/backoffice/order-management/backoffice-order-details-page'
import {
  getFixtures,
  CustomerFixture,
  PriceProductFixture,
  ProductFixture,
  ProductOfferFixture,
} from '@support/types/dynamic-fixtures'

interface BackofficeOrderDynamicFixtures {
  customer: CustomerFixture
  product: ProductFixture
  productPrice: PriceProductFixture
  productOffer: ProductOfferFixture
}

interface BackofficeOrderStaticFixtures {
  defaultPassword: string
}

const backofficeLoginPage = new BackofficeLoginPage()
const backofficeOrderListPage = new BackofficeOrderListPage()
const backofficeOrderDetailsPage = new BackofficeOrderDetailsPage()

let dynamicFixtures: BackofficeOrderDynamicFixtures
let staticFixtures: BackofficeOrderStaticFixtures
let orderReference: string

context('Order management', () => {
  before(function () {
    // the customer is created per run, so it has no addresses or carts to reset here
    ;({ dynamicFixtures, staticFixtures } = getFixtures<
      BackofficeOrderDynamicFixtures,
      BackofficeOrderStaticFixtures
    >())

    // placing an order for processing
    cy.placeOrderViaGlue(
      dynamicFixtures.customer.email,
      staticFixtures.defaultPassword,
      dynamicFixtures.product.sku,
      checkoutData.glueShipment.id,
      checkoutData.gluePayment.providerName,
      checkoutData.gluePayment.methodName,
      dynamicFixtures.productOffer.product_offer_reference,
      dynamicFixtures.productOffer.merchant_reference
    ).then((response: string) => {
      orderReference = response
    })
  })

  it('can trigger OMS events for an order', () => {
    // if the tests are run on an env without active scheduler, we will need to trigger oms transition using CLI commands
    // make sure the location from which you run cypress tests has access to Spryker env
    cy.triggerOmsTransition()
    backofficeLoginPage.login(
      userCredentials.backofficeUser.email,
      userCredentials.backofficeUser.password
    )
    backofficeOrderListPage.visit()
    backofficeOrderListPage.filterOrdersByReference(orderReference)
    // verify that the order placed in before hook exists in BO as the first order in the list
    backofficeOrderListPage
      .getOrderReference(0)
      .should('have.text', orderReference)
    backofficeOrderListPage.viewOrderByPosition(0)
    // check that price for the product is still as it was in the shop
    cy.formatDisplayPrice(
      dynamicFixtures.productPrice.money_value.gross_amount
    ).then((expectedPrice: string) => {
      backofficeOrderDetailsPage
        .getOrderSubtotal()
        .should('contain', expectedPrice)
    })
    cy.triggerOmsTransition()
    cy.waitForOrderProcessing('grace period started', 20)
    // clicks the oms trigger with the name 'skip grace period'
    backofficeOrderDetailsPage.triggerOms('skip grace period')
    // clicks the oms trigger with the name 'Pay'
    backofficeOrderDetailsPage.triggerOms('Pay')
    backofficeOrderDetailsPage
      .getSuccessfulOrderMessages()
      .should('contain', 'Status change triggered successfully.')
    backofficeOrderDetailsPage
      .getOrderItemHistory(dynamicFixtures.product.sku)
      .should('contain', 'tax invoice submitted')
  })

  it('checks customer email in order details page', () => {
    backofficeLoginPage.login(
      userCredentials.backofficeUser.email,
      userCredentials.backofficeUser.password
    )
    backofficeOrderListPage.visit()
    backofficeOrderListPage.filterOrdersByReference(orderReference)
    // verify that the order placed in before hook exists in BO as the first order in the list
    backofficeOrderListPage
      .getOrderReference(0)
      .should('have.text', orderReference)
    backofficeOrderListPage.viewOrderByPosition(0)
    // check that customer email is correct on the order details page
    backofficeOrderDetailsPage
      .getCustomerEmail()
      .should('have.text', dynamicFixtures.customer.email)
  })
})
