import customerCredentials from '@fixtures/customer-data.json'
import productData from '@fixtures/product-data.json'
import checkoutData from '@fixtures/checkout-data.json'
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
import { GlueAddressesScenarios } from '@support/scenarios/glue/glue-addresses-scenarios'
import { GlueCartsScenarios } from '@support/scenarios/glue/glue-carts-scenarios'
import { StorefrontCartScenarios } from '@support/scenarios/storefront/storefront-cart-scenarios'

const glueAddressesScenarios = new GlueAddressesScenarios()
const glueCartsScenarios = new GlueCartsScenarios()
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

let orderGrandTotal: string
let createdOrderReference: string

context('Customer orders', () => {
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

    // place an order through the real storefront checkout flow — this customer's
    // business unit has the Purchasing Control feature enabled, so a cost center and
    // budget must be selected on the summary page before an order can be placed
    // (there is no Glue API support for this, so it can't be set up via the API)
    storefrontLoginPage.login(
      customerCredentials.email,
      customerCredentials.password
    )
    storefrontCartScenarios.createNewCart()
    search.findProduct(productData.availableProduct.abstractSku)
    productDetailsPage
      .getProductName()
      .should('contain', productData.availableProduct.name)
    productDetailsPage.addProductToCart()
    cartIcon.getCartTrigger().click()
    cartPage
      .getCartItemPrice(productData.availableProduct.concreteSku)
      .should('contain', productData.availableProduct.price)
    cartPage.getCheckoutButton().click()
    checkoutAddress.provideExistingAddress()
    checkoutShipping.provideShipment(checkoutData.storefrontShipment.name)
    checkoutPayment.providePayment(checkoutData.storefrontPayment.name)
    checkoutSummary.selectCostCenter(checkoutData.storefrontCostCenter.name)
    checkoutSummary.selectBudget(checkoutData.storefrontBudget.name)
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
      customerCredentials.email,
      customerCredentials.password
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
      customerCredentials.email,
      customerCredentials.password
    )

    // open customer overview page and click on the first view order button
    storefrontCustomerOverviewPage.visit()
    storefrontCustomerOverviewPage.getFirstOrderViewActionButton().click()

    // assert we are on the correct order details page
    storefrontCustomerOrderDetailsPage.getPageTitle().contains('Order Details')
    storefrontCustomerOrderDetailsPage
      .getOrderInfoBlockOrderReference()
      .should('contain', createdOrderReference)

    // assert that the order grand total is displayed correctly in summary
    storefrontCustomerOrderDetailsPage
      .getOrderSummaryGrandTotal()
      .should('contain', orderGrandTotal)
  })
})
