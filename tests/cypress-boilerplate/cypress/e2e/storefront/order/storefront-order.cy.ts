import { StorefrontLoginPage } from '@support/page-objects/storefront/login/storefront-login-page'
import { StorefrontSearchResultsPage } from '@support/page-objects/storefront/search/storefront-search-results-page'
import { StorefrontProductDetailsPage } from '@support/page-objects/storefront/product/storefront-product-details-page'
import { StorefrontCartPage } from '@support/page-objects/storefront/cart/storefront-cart-page'
import { StorefrontCartFlyout } from '@support/page-objects/storefront/cart/storefront-cart-flyout'
import { StorefrontCheckoutAddressPage } from '@support/page-objects/storefront/checkout/storefront-checkout-address-page'
import { StorefrontCheckoutShippingPage } from '@support/page-objects/storefront/checkout/storefront-checkout-shipping-page'
import { StorefrontCheckoutPaymentPage } from '@support/page-objects/storefront/checkout/storefront-checkout-payment-page'
import { StorefrontCheckoutSummaryPage } from '@support/page-objects/storefront/checkout/storefront-checkout-summary-page'
import { StorefrontCheckoutSuccessPage } from '@support/page-objects/storefront/checkout/storefront-checkout-success-page'
import { StorefrontCustomerOverviewPage } from '@support/page-objects/storefront/customer/storefront-customer-overview-page'
import { StorefrontCustomerOrderDetailsPage } from '@support/page-objects/storefront/customer/storefront-customer-order-details-page'
import { StorefrontCartScenarios } from '@support/scenarios/storefront/storefront-cart-scenarios'
import {
  getFixtures,
  getProductName,
  BudgetFixture,
  CostCenterFixture,
  CustomerFixture,
  PriceProductFixture,
  ProductFixture,
  ShipmentMethodFixture,
} from '@support/types/dynamic-fixtures'

interface OrderDynamicFixtures {
  customer: CustomerFixture
  product: ProductFixture
  productPrice: PriceProductFixture
  shipmentMethod: ShipmentMethodFixture
  costCenter: CostCenterFixture
  budget: BudgetFixture
}

interface OrderStaticFixtures {
  defaultPassword: string
  paymentMethodKey: string
}

const storefrontCartScenarios = new StorefrontCartScenarios()
const storefrontLoginPage = new StorefrontLoginPage()
const search = new StorefrontSearchResultsPage()
const productDetailsPage = new StorefrontProductDetailsPage()
const cartPage = new StorefrontCartPage()
const cartIcon = new StorefrontCartFlyout()
const checkoutAddress = new StorefrontCheckoutAddressPage()
const checkoutShipping = new StorefrontCheckoutShippingPage()
const checkoutPayment = new StorefrontCheckoutPaymentPage()
const checkoutSummary = new StorefrontCheckoutSummaryPage()
const checkoutSuccess = new StorefrontCheckoutSuccessPage()
const storefrontCustomerOverviewPage = new StorefrontCustomerOverviewPage()
const storefrontCustomerOrderDetailsPage =
  new StorefrontCustomerOrderDetailsPage()

let dynamicFixtures: OrderDynamicFixtures
let staticFixtures: OrderStaticFixtures
let orderGrandTotal: string
let createdOrderReference: string

context('Customer orders', () => {
  before(() => {
    ;({ dynamicFixtures, staticFixtures } = getFixtures<
      OrderDynamicFixtures,
      OrderStaticFixtures
    >())

    storefrontLoginPage.login(
      dynamicFixtures.customer.email,
      staticFixtures.defaultPassword
    )
    storefrontCartScenarios.createNewCart()
    search.findProduct(dynamicFixtures.product.abstract_sku)
    productDetailsPage
      .getProductName()
      .should('contain', getProductName(dynamicFixtures.product))
    productDetailsPage.addProductToCart()
    cartIcon.getCartTrigger().click()
    cy.formatDisplayPrice(
      dynamicFixtures.productPrice.money_value.gross_amount
    ).then((expectedPrice: string) => {
      cartPage
        .getCartItemPrice(dynamicFixtures.product.sku)
        .should('contain', expectedPrice)
    })
    cartPage.getCheckoutButton().click()
    checkoutAddress.provideExistingAddress()
    checkoutShipping.provideShipment(dynamicFixtures.shipmentMethod.name)
    checkoutPayment.providePayment(staticFixtures.paymentMethodKey)
    checkoutSummary.selectCostCenter(dynamicFixtures.costCenter.name)
    checkoutSummary.selectBudget(dynamicFixtures.budget.name)
    checkoutSummary.applyCostCenterAndBudget()
    checkoutSummary
      .getGrandTotalAmount()
      .invoke('text')
      .then((text) => {
        orderGrandTotal = text.trim()
      })
    checkoutSummary.completeOrder()
    checkoutSuccess.checkOrderSuccess()
    checkoutSuccess.getOrderReference().then((reference) => {
      createdOrderReference = reference
    })
  })

  it('can see placed order in orders table', () => {
    // login as a customer
    storefrontLoginPage.login(
      dynamicFixtures.customer.email,
      staticFixtures.defaultPassword
    )
    // open customer overview page and assert order table is visible
    storefrontCustomerOverviewPage.visit()
    storefrontCustomerOverviewPage.getOrdersTable().should('be.visible')

    // assert that the first order row contains the correct grand total of the order
    storefrontCustomerOverviewPage
      .getFirstOrderRowPrice()
      .should('contain', orderGrandTotal)
  })

  it('can open order details page', () => {
    // login as a customer
    storefrontLoginPage.login(
      dynamicFixtures.customer.email,
      staticFixtures.defaultPassword
    )

    // open customer overview page and click on the first view order button
    storefrontCustomerOverviewPage.visit()
    storefrontCustomerOverviewPage.getFirstOrderViewActionButton().click()

    // assert we are on the correct order details page - by URL, since the heading is translated
    cy.location('pathname').should('include', '/customer/order/details')
    storefrontCustomerOrderDetailsPage
      .getOrderInfoBlockOrderReference()
      .should('contain', createdOrderReference)

    // assert that the order grand total is displayed correctly in summary
    storefrontCustomerOrderDetailsPage
      .getOrderSummaryGrandTotal()
      .should('contain', orderGrandTotal)
  })
})
