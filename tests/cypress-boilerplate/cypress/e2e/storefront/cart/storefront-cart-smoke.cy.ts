import { StorefrontLoginPage } from '@support/page-objects/storefront/login/storefront-login-page'
import { StorefrontHomePage } from '@support/page-objects/storefront/home/storefront-home-page'
import { StorefrontSearchResultsPage } from '@support/page-objects/storefront/search/storefront-search-results-page'
import { StorefrontProductDetailsPage } from '@support/page-objects/storefront/product/storefront-product-details-page'
import { StorefrontCartPage } from '@support/page-objects/storefront/cart/storefront-cart-page'
import { StorefrontCartFlyout } from '@support/page-objects/storefront/cart/storefront-cart-flyout'
import { StorefrontCartScenarios } from '@support/scenarios/storefront/storefront-cart-scenarios'
import {
  getFixtures,
  getProductName,
} from '@support/fixture-helper/fixture-helper'
import {
  CartSmokeDynamicFixtures,
  CartSmokeStaticFixtures,
} from '@support/types/storefront'

const storefrontLoginPage = new StorefrontLoginPage()
const homePage = new StorefrontHomePage()
const search = new StorefrontSearchResultsPage()
const productDetailsPage = new StorefrontProductDetailsPage()
const cartPage = new StorefrontCartPage()
const cartIcon = new StorefrontCartFlyout()
const storefrontCartScenarios = new StorefrontCartScenarios()

let dynamicFixtures: CartSmokeDynamicFixtures
let staticFixtures: CartSmokeStaticFixtures

context('Storefront smoke: homepage to cart', () => {
  before(() => {
    // the customer is created per run, so there is no leftover cart or address state to
    // reset here — the starting state is empty by construction
    ;({ dynamicFixtures, staticFixtures } = getFixtures<
      CartSmokeDynamicFixtures,
      CartSmokeStaticFixtures
    >())
  })

  it('can find a product from the homepage and add it to the cart', () => {
    storefrontLoginPage.login(
      dynamicFixtures.customer.email,
      staticFixtures.defaultPassword
    )
    storefrontCartScenarios.createNewCart()

    homePage.visit()
    search.findProduct(dynamicFixtures.product.abstract_sku)
    productDetailsPage
      .getProductName()
      .should('contain', getProductName(dynamicFixtures.product))

    productDetailsPage.addProductToCart()
    cartIcon.getCartBadge().should('contain', '1')
    cartIcon.getCartTrigger().click()

    cy.formatDisplayPrice(
      dynamicFixtures.productPrice.money_value.gross_amount
    ).then((expectedPrice: string) => {
      cartPage
        .getCartItemPrice(dynamicFixtures.product.sku)
        .should('contain', expectedPrice)
    })
  })
})
