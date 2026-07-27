import customerCredentials from '@fixtures/customer-data.json'
import productData from '@fixtures/product-data.json'
import { StorefrontLoginPage } from '@support/page-objects/storefront/login/storefront-login-page'
import { StorefrontHomePage } from '@support/page-objects/storefront/home/storefront-home-page'
import { StorefrontSearchResultsPage } from '@support/page-objects/storefront/search/storefront-search-results-page'
import { StorefrontProductDetailsPage } from '@support/page-objects/storefront/product/storefront-product-details-page'
import { StorefrontCartPage } from '@support/page-objects/storefront/cart/storefront-cart-page'
import { StorefrontCartFlyout } from '@support/page-objects/storefront/cart/storefront-cart-flyout'
import { GlueCartsScenarios } from '@support/scenarios/glue/glue-carts-scenarios'
import { StorefrontCartScenarios } from '@support/scenarios/storefront/storefront-cart-scenarios'

const storefrontLoginPage = new StorefrontLoginPage()
const homePage = new StorefrontHomePage()
const search = new StorefrontSearchResultsPage()
const productDetailsPage = new StorefrontProductDetailsPage()
const cartPage = new StorefrontCartPage()
const cartIcon = new StorefrontCartFlyout()
const glueCartsScenarios = new GlueCartsScenarios()
const storefrontCartScenarios = new StorefrontCartScenarios()

before(() => {
  // reset customer carts so the smoke test starts from a known, empty state
  glueCartsScenarios.deleteAllShoppingCarts(
    customerCredentials.email,
    customerCredentials.password
  )
})

context('Storefront smoke: homepage to cart', () => {
  it('can find a product from the homepage and add it to the cart', () => {
    storefrontLoginPage.login(
      customerCredentials.email,
      customerCredentials.password
    )
    storefrontCartScenarios.createNewCart()

    homePage.visit()
    search.findProduct(productData.availableProduct.abstractSku)
    productDetailsPage
      .getProductName()
      .should('contain', productData.availableProduct.name)

    productDetailsPage.addProductToCart()
    cartIcon.getCartBadge().should('contain', '1')
    cartIcon.getCartTrigger().click()

    cartPage
      .getCartItem(productData.availableProduct.concreteSku)
      .find('[itemprop="price"]')
      .should('contain', productData.availableProduct.price)
  })
})
