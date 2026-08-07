import checkoutData from '@fixtures/shared/checkout-data.json'
import { BackofficeLoginPage } from '@support/page-objects/backoffice/login/backoffice-login-page'
import { BackofficeOrderListPage } from '@support/page-objects/backoffice/order-management/backoffice-order-list-page'
import { BackofficeOrderDetailsPage } from '@support/page-objects/backoffice/order-management/backoffice-order-details-page'
import { GlueCheckoutScenarios } from '@support/scenarios/glue/glue-checkout-scenarios'
import { OmsTransitionScenarios } from '@support/scenarios/backoffice/oms-transition-scenarios'
import { getFixtures } from '@support/fixture-helper/fixture-helper'
import {
  BackofficeOrderDynamicFixtures,
  BackofficeOrderStaticFixtures,
} from '@support/types/backoffice'

const backofficeLoginPage = new BackofficeLoginPage()
const backofficeOrderListPage = new BackofficeOrderListPage()
const backofficeOrderDetailsPage = new BackofficeOrderDetailsPage()
const glueCheckoutScenarios = new GlueCheckoutScenarios()
const omsTransitionScenarios = new OmsTransitionScenarios()

let dynamicFixtures: BackofficeOrderDynamicFixtures
let staticFixtures: BackofficeOrderStaticFixtures
let createdOrderReference: string

context('Order management', () => {
  before(function () {
    // the customer is created per run, so it has no addresses or carts to reset here
    ;({ dynamicFixtures, staticFixtures } = getFixtures<
      BackofficeOrderDynamicFixtures,
      BackofficeOrderStaticFixtures
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

  it('can trigger OMS events for an order', () => {
    // if the tests are run on an env without active scheduler, we will need to trigger oms transition using CLI commands
    // make sure the location from which you run cypress tests has access to Spryker env
    omsTransitionScenarios.triggerOmsTransition()
    backofficeLoginPage.login(
      dynamicFixtures.backofficeUser.username,
      staticFixtures.defaultPassword
    )
    backofficeOrderListPage.visit()
    backofficeOrderListPage.filterOrdersByReference(createdOrderReference)
    // verify that the order placed in before hook exists in BO as the first order in the list
    backofficeOrderListPage
      .getOrderReference(0)
      .should('have.text', createdOrderReference)
    backofficeOrderListPage.viewOrderByPosition(0)
    // check that price for the product is still as it was in the shop
    cy.formatDisplayPrice(
      dynamicFixtures.productPrice.money_value.gross_amount
    ).then((expectedPrice: string) => {
      backofficeOrderDetailsPage
        .getOrderSubtotal()
        .should('contain', expectedPrice)
    })
    // if the tests are run on an env without active scheduler, we will need to trigger oms transition using CLI commands
    // make sure the location from which you run cypress tests has access to Spryker env
    omsTransitionScenarios.triggerOmsTransition()
    backofficeOrderDetailsPage.clickOmsTriggerIfOffered('skip grace period')
    backofficeOrderDetailsPage.clickOmsTriggerIfOffered('Pay')
    omsTransitionScenarios.triggerOmsTransition()
    backofficeOrderDetailsPage.waitForOrderItemStateInHistory(
      dynamicFixtures.product.sku,
      'tax invoice submitted',
      20
    )
  })

  it('checks customer email in order details page', () => {
    backofficeLoginPage.login(
      dynamicFixtures.backofficeUser.username,
      staticFixtures.defaultPassword
    )
    backofficeOrderListPage.visit()
    backofficeOrderListPage.filterOrdersByReference(createdOrderReference)
    // verify that the order placed in before hook exists in BO as the first order in the list
    backofficeOrderListPage
      .getOrderReference(0)
      .should('have.text', createdOrderReference)
    backofficeOrderListPage.viewOrderByPosition(0)
    // check that customer email is correct on the order details page
    backofficeOrderDetailsPage
      .getCustomerEmail()
      .should('have.text', dynamicFixtures.customer.email)
  })
})
