import { StorefrontLoginPage } from '@support/page-objects/storefront/login/storefront-login-page'
import { StorefrontHomePage } from '@support/page-objects/storefront/home/storefront-home-page'
import { StorefrontSearchResultsPage } from '@support/page-objects/storefront/search/storefront-search-results-page'
import { StorefrontProductDetailsPage } from '@support/page-objects/storefront/product/storefront-product-details-page'
import { StorefrontCartPage } from '@support/page-objects/storefront/cart/storefront-cart-page'
import { StorefrontCartScenarios } from '@support/scenarios/storefront/storefront-cart-scenarios'
import { getFixtures } from '@support/fixture-helper/fixture-helper'
import {
  CartFreeShippingHintDynamicFixtures,
  CartFreeShippingHintStaticFixtures,
} from '@support/types/storefront'

const storefrontLoginPage = new StorefrontLoginPage()
const homePage = new StorefrontHomePage()
const search = new StorefrontSearchResultsPage()
const productDetailsPage = new StorefrontProductDetailsPage()
const cartPage = new StorefrontCartPage()
const storefrontCartScenarios = new StorefrontCartScenarios()

let dynamicFixtures: CartFreeShippingHintDynamicFixtures
let staticFixtures: CartFreeShippingHintStaticFixtures

const openProductInNewCart = (): void => {
  storefrontLoginPage.login(
    dynamicFixtures.customer.email,
    staticFixtures.defaultPassword
  )
  storefrontCartScenarios.createNewCart()

  homePage.visit()
  search.findProduct(dynamicFixtures.product.abstract_sku)
}

context('Storefront cart: free shipping hint', () => {
  before(() => {
    // the customer and product are created per run; every test starts in a new empty cart
    ;({ dynamicFixtures, staticFixtures } = getFixtures<
      CartFreeShippingHintDynamicFixtures,
      CartFreeShippingHintStaticFixtures
    >())
  })

  it('shows the missing amount when the cart value is below the free shipping threshold', () => {
    openProductInNewCart()
    productDetailsPage.addProductToCart()

    cartPage.visit()
    cartPage
      .getFreeShippingHintRemaining()
      .should('contain', staticFixtures.freeShippingHintRemainingText)
    cartPage
      .getFreeShippingHint()
      .should('not.contain', staticFixtures.freeShippingHintReachedText)
  })

  it('shows that free shipping is reached when the cart value is above the threshold', () => {
    openProductInNewCart()
    productDetailsPage.increaseAddToCartQuantity()
    productDetailsPage.getAddToCartQuantityInput().should('have.value', '2')
    productDetailsPage.addProductToCart()

    cartPage.visit()
    cartPage
      .getFreeShippingHintReached()
      .should('contain', staticFixtures.freeShippingHintReachedText)
  })
})
