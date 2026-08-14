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

    this.quickOrderPage.getQuantityInput(rowIndex).should('have.value', '1')

    if (quantity > 1) {
      for (let i = 1; i < quantity; i++) {
        this.quickOrderPage.incrementQuantity(rowIndex)
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
