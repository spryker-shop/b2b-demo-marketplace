import checkoutData from '@fixtures/checkout-data.json'
import userCredentials from '@fixtures/user-data.json'
import { MerchantLoginPage } from '@support/page-objects/merchant-portal/login/merchant-portal-login-page'
import { MerchantOrderListPage } from '@support/page-objects/merchant-portal/order-management/merchant-portal-order-list-page'
import { MerchantOrderDetailsPage } from '@support/page-objects/merchant-portal/order-management/merchant-portal-order-details-page'
import { BackofficeLoginPage } from '@support/page-objects/backoffice/login/backoffice-login-page'
import { BackofficeOrderListPage } from '@support/page-objects/backoffice/order-management/backoffice-order-list-page'
import { BackofficeOrderDetailsPage } from '@support/page-objects/backoffice/order-management/backoffice-order-details-page'
import { GlueCheckoutScenarios } from '@support/scenarios/glue/glue-checkout-scenarios'
import { OmsTransitionScenarios } from '@support/scenarios/backoffice/oms-transition-scenarios'
import {
  getFixtures,
  CustomerFixture,
  PriceProductFixture,
  ProductFixture,
  ProductOfferFixture,
} from '@support/types/dynamic-fixtures'

interface MerchantOrderDynamicFixtures {
  customer: CustomerFixture
  product: ProductFixture
  productPrice: PriceProductFixture
  productOffer: ProductOfferFixture
}

interface MerchantOrderStaticFixtures {
  defaultPassword: string
}

const merchantLoginPage = new MerchantLoginPage()
const merchantOrderListPage = new MerchantOrderListPage()
const merchantOrderDetailsPage = new MerchantOrderDetailsPage()
const backofficeLoginPage = new BackofficeLoginPage()
const backofficeOrderListPage = new BackofficeOrderListPage()
const backofficeOrderDetailsPage = new BackofficeOrderDetailsPage()
const glueCheckoutScenarios = new GlueCheckoutScenarios()
const omsTransitionScenarios = new OmsTransitionScenarios()

let dynamicFixtures: MerchantOrderDynamicFixtures
let staticFixtures: MerchantOrderStaticFixtures
let createdOrderReference: string

context('Merchant Order management', () => {
  before(function () {
    // the customer is created per run, so it has no addresses or carts to reset here
    ;({ dynamicFixtures, staticFixtures } = getFixtures<
      MerchantOrderDynamicFixtures,
      MerchantOrderStaticFixtures
    >())

    // placing an order for processing
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

  it('merchant can process orders', () => {
    // if the tests are run on an env without active scheduler, we will need to trigger oms transition using CLI commands
    // make sure the location from which you run cypress tests has access to Spryker env
    omsTransitionScenarios.triggerOmsTransition()
    backofficeLoginPage.login(
      userCredentials.backofficeUser.email,
      userCredentials.backofficeUser.password
    )
    backofficeOrderListPage.visit()
    backofficeOrderListPage.filterOrdersByReference(createdOrderReference)
    backofficeOrderListPage.viewOrderByReference(createdOrderReference)
    omsTransitionScenarios.triggerOmsTransition()
    backofficeOrderDetailsPage.clickOmsTriggerIfOffered('skip grace period')
    backofficeOrderDetailsPage.clickOmsTriggerIfOffered('Pay')
    // if the tests are run on an env without active scheduler, e.g. local env, we will need to trigger oms transition using CLI commands
    // make sure the location from which you run cypress tests has access to Spryker env
    omsTransitionScenarios.triggerOmsTransition()
    omsTransitionScenarios.waitForOrderProcessing('sent to merchant', 20)
    //process order in merchant portal
    merchantLoginPage.login(
      userCredentials.merchantPortalUser.email,
      userCredentials.merchantPortalUser.password
    )
    // wait until login redirects away from the login page (session established)
    cy.url({ timeout: 20000 }).should(
      'not.include',
      '/security-merchant-portal-gui/login'
    )
    // now navigate to orders page
    merchantOrderListPage.visit()
    // verify that the order placed in before hook exists and was passed to merchant
    merchantOrderListPage.viewOrderByReference(createdOrderReference)
    // check that price for the product is still as it was in the shop
    cy.formatDisplayPrice(
      dynamicFixtures.productPrice.money_value.gross_amount
    ).then((expectedPrice: string) => {
      merchantOrderDetailsPage
        .getOrderSubTotals()
        .should('contain', expectedPrice)
    })
    // clicks the oms trigger with the name 'Pay'
    merchantOrderDetailsPage.triggerOms('Ship')
    merchantOrderDetailsPage.getOrderItemsStates().should('contain', 'Shipped')
    merchantOrderDetailsPage.openOrderTab('Items')
    merchantOrderDetailsPage
      .getOrderItemState(dynamicFixtures.product.sku)
      .should('contain', 'shipped')
  })
})
