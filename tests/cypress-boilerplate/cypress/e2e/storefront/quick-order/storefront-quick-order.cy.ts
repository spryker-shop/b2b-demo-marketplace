// All shop data this spec needs is created before it runs by
// cypress/fixtures/storefront/quick-order/dynamic-storefront-quick-order.json, so nothing
// here depends on demodata: a fresh customer (with a company, business unit and cart
// permissions), a fresh product with price and stock, and — for the merchant filter test —
// a fresh active merchant that owns that product and sells it through a product offer are
// all generated per run.
import { StorefrontLoginPage } from '@support/page-objects/storefront/login/storefront-login-page'
import { StorefrontQuickOrderPage } from '@support/page-objects/storefront/quick-order/storefront-quick-order-page'
import { StorefrontCartPage } from '@support/page-objects/storefront/cart/storefront-cart-page'
import { StorefrontQuickOrderScenarios } from '@support/scenarios/storefront/storefront-quick-order-scenarios'
import { StorefrontCartScenarios } from '@support/scenarios/storefront/storefront-cart-scenarios'
import {
  getFixtures,
  getProductName,
  CustomerFixture,
  MerchantFixture,
  ProductFixture,
} from '@support/types/dynamic-fixtures'

interface QuickOrderDynamicFixtures {
  customer: CustomerFixture
  product: ProductFixture
  merchant: MerchantFixture
}

interface QuickOrderStaticFixtures {
  defaultPassword: string
  quantity: number
}

const storefrontLoginPage = new StorefrontLoginPage()
const storefrontCartPage = new StorefrontCartPage()
const storefrontQuickOrderPage = new StorefrontQuickOrderPage()

const storefrontQuickOrderScenarios = new StorefrontQuickOrderScenarios()
const storefrontCartScenarios = new StorefrontCartScenarios()

let dynamicFixtures: QuickOrderDynamicFixtures
let staticFixtures: QuickOrderStaticFixtures

// The product name has to be searchable by the quick-order autocomplete, which never
// returns haveFullProduct's own default (`uniqid('Product #', true)`, e.g.
// "Product #6a6ccefaafff16.65668963"). The payload therefore takes the name as a
// {{QUICK_ORDER_PRODUCT_NAME}} placeholder. It is set here at module scope — which runs when
// the spec is loaded, before the root `before` hook in support/e2e.ts requests the fixtures —
// and carries a timestamp so a previous run's product can never be matched instead of this
// run's. If the ordering ever changed, fixture loading would fail loudly on the unresolved
// placeholder rather than silently using a stale name.
Cypress.env('QUICK_ORDER_PRODUCT_NAME', `Cypress Quick Order ${Date.now()}`)

context('Quick order', () => {
  before(() => {
    ;({ dynamicFixtures, staticFixtures } = getFixtures<
      QuickOrderDynamicFixtures,
      QuickOrderStaticFixtures
    >())
  })

  beforeEach(() => {
    storefrontLoginPage.login(
      dynamicFixtures.customer.email,
      staticFixtures.defaultPassword
    )

    // unlike the single-test specs, every test here fills a cart, so each one needs its own
    // empty cart rather than inheriting whatever the previous test left active. Creating a
    // fresh cart is preferred over deleting the old ones through Glue: the storefront
    // customer created by the fixtures is a B2B company user, and the Glue storefront
    // token endpoint does not authenticate it.
    storefrontCartScenarios.createNewCart()

    storefrontQuickOrderPage.visit()
  })

  it('can search product by SKU and to cart', () => {
    cy.intercept('GET', '**/product-search/product-concrete-search**').as(
      'productSearch'
    )
    storefrontQuickOrderScenarios.addProduct(
      dynamicFixtures.product.sku,
      staticFixtures.quantity
    )
    cy.wait('@productSearch')
    storefrontQuickOrderPage.addToCart()

    storefrontCartPage
      .getCartItemsList()
      .should('contain', dynamicFixtures.product.sku)
  })

  it('can search product by name and add to cart', () => {
    const productName = getProductName(dynamicFixtures.product)

    cy.intercept('GET', '**/product-search/product-concrete-search**').as(
      'productSearch'
    )
    storefrontQuickOrderScenarios.addProduct(
      productName,
      staticFixtures.quantity
    )
    cy.wait('@productSearch')
    storefrontQuickOrderPage.addToCart()

    storefrontCartPage.getCartItemsList().should('contain', productName)
  })

  it('can add merchant-specific product to cart', () => {
    // The per-row merchant select is used rather than the form's top-level merchant filter:
    // that filter is backed by a merchant search that is requested without pagination
    // parameters, so it only ever renders the first page of merchants
    // (MerchantSearchConfig::PAGINATION_DEFAULT_ITEMS_PER_PAGE = 10) while this project's
    // demodata alone already defines 16 — a merchant created per run is therefore never
    // guaranteed to be listed there. The row-level select is scoped to the merchants
    // offering the product in that row, which is exactly the offer the fixtures created.
    cy.intercept('GET', '**/product-search/product-concrete-search**').as(
      'productSearch'
    )
    storefrontQuickOrderScenarios.addProduct(
      dynamicFixtures.product.sku,
      staticFixtures.quantity,
      dynamicFixtures.merchant.name
    )
    cy.wait('@productSearch')
    storefrontQuickOrderPage.addToCart()

    storefrontCartPage
      .getCartItemsList()
      .should('contain', dynamicFixtures.product.sku)
  })

  it('can add product for checkout', () => {
    cy.intercept('GET', '**/product-search/product-concrete-search**').as(
      'productSearch'
    )
    storefrontQuickOrderScenarios.addProduct(
      dynamicFixtures.product.sku,
      staticFixtures.quantity
    )
    cy.wait('@productSearch')

    storefrontQuickOrderPage.createOrder()
  })
})
