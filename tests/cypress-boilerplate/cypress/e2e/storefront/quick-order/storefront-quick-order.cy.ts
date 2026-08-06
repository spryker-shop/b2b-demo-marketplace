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
} from '@support/fixture-helper/fixture-helper'
import {
  QuickOrderDynamicFixtures,
  QuickOrderStaticFixtures,
} from '@support/types/storefront'

const storefrontLoginPage = new StorefrontLoginPage()
const storefrontCartPage = new StorefrontCartPage()
const storefrontQuickOrderPage = new StorefrontQuickOrderPage()

const storefrontQuickOrderScenarios = new StorefrontQuickOrderScenarios()
const storefrontCartScenarios = new StorefrontCartScenarios()

let dynamicFixtures: QuickOrderDynamicFixtures
let staticFixtures: QuickOrderStaticFixtures

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
