// Everything this spec checks out with is created before it runs by
// cypress/fixtures/storefront/checkout/dynamic-storefront-checkout.json: the customer,
// its company with a business unit address to ship to, the product with price and stock,
// the shipment method, and the cost center plus budget the Purchasing Control feature
// requires on the summary step. The only value that still comes from a static fixture is
// the payment method name — payment methods are bound to a payment plugin registered in
// project code (Pyz\Yves\DummyPayment), so a generated one would never be rendered.
// The generated product has no merchant or product offer behind it, so the checkout
// offers the Dummy Payment methods rather than the marketplace ones.
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

interface CheckoutDynamicFixtures {
  customer: CustomerFixture
  product: ProductFixture
  productPrice: PriceProductFixture
  shipmentMethod: ShipmentMethodFixture
  costCenter: CostCenterFixture
  budget: BudgetFixture
}

interface CheckoutStaticFixtures {
  defaultPassword: string
  paymentMethodName: string
}

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
const storefrontCartScenarios = new StorefrontCartScenarios()

let dynamicFixtures: CheckoutDynamicFixtures
let staticFixtures: CheckoutStaticFixtures

context('Customer checkout', () => {
  before(() => {
    // the customer is created per run, so it starts without carts or addresses and
    // nothing has to be reset here
    ;({ dynamicFixtures, staticFixtures } = getFixtures<
      CheckoutDynamicFixtures,
      CheckoutStaticFixtures
    >())
  })

  it('can place order on storefront', () => {
    // here we use method from login page object to open login page, enter credentials and login
    storefrontLoginPage.login(
      dynamicFixtures.customer.email,
      staticFixtures.defaultPassword
    )
    storefrontCartScenarios.createNewCart()
    // here we use search page object to find a product and go to its PDP
    search.findProduct(dynamicFixtures.product.abstract_sku)
    // here we check that the correct product PDP was opened - this is an assertion, other assertions can be added as needed
    productDetailsPage
      .getProductName()
      .should('contain', getProductName(dynamicFixtures.product))
    productDetailsPage.addProductToCart()
    cartIcon.getCartTrigger().click()
    // another assertion checking that price in cart is as expected
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
    checkoutPayment.providePayment(staticFixtures.paymentMethodName)
    // this customer's business unit has a cost center, which turns on the Purchasing
    // Control feature for it: a cost center and budget must be selected on the summary
    // page before an order can be placed
    checkoutSummary.selectCostCenter(dynamicFixtures.costCenter.name)
    checkoutSummary.selectBudget(dynamicFixtures.budget.name)
    checkoutSummary.applyCostCenterAndBudget()
    checkoutSummary.completeOrder()
    checkoutSuccess.checkOrderSuccess()
  })
})
