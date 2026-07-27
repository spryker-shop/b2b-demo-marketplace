import { StorefrontQuickOrderPage } from '../../page-objects/storefront/quick-order/storefront-quick-order-page'

export class StorefrontQuickOrderScenarios {
  quickOrderPage = new StorefrontQuickOrderPage()

  addProduct = (
    skuOrName: string,
    quantity = 1,
    merchantName?: string,
    rowIndex = 0
  ): void => {
    this.quickOrderPage.searchProduct(rowIndex, skuOrName)
    this.quickOrderPage.applySuggestedProduct(skuOrName)

    if (merchantName) {
      this.quickOrderPage.selectProductMerchant(rowIndex, merchantName)
    }

    // selecting the product (and merchant, if any) triggers its own ajax-driven
    // re-render of the row; wait for that to settle at its default quantity of 1
    // before clicking increment, otherwise the first click can land on a row that's
    // about to be replaced and get lost
    this.quickOrderPage.getQuantityInput(rowIndex).should('have.value', '1')

    if (quantity > 1) {
      for (let i = 1; i < quantity; i++) {
        this.quickOrderPage.incrementQuantity(rowIndex)
        // each click triggers an ajax-driven re-render of the row; wait for the
        // quantity input to reflect this click before firing the next one, otherwise
        // rapid consecutive clicks can outrun the render and increments get lost
        this.quickOrderPage
          .getQuantityInput(rowIndex)
          .should('have.value', String(i + 1))
      }
    }

    this.quickOrderPage
      .getQuantityInput(rowIndex)
      .should('have.value', quantity)
  }
}
