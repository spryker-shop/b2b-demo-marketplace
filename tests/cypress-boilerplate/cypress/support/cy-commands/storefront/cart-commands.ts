import { StorefrontCreateCartPage } from '../../page-objects/storefront/cart/storefront-create-cart-page'
const storefrontCreateCartPage = new StorefrontCreateCartPage()

//creates a new cart with name matching datetime of creation
Cypress.Commands.add('createNewCart', (): Cypress.Chainable => {
  const currentDateTime = new Date()
  storefrontCreateCartPage.visit()
  storefrontCreateCartPage
    .getCartNameField()
    .type(currentDateTime.toISOString())

  return storefrontCreateCartPage.createCart()
})
