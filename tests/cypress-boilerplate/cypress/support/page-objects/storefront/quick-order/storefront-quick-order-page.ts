import { AbstractPage } from '../../abstract-page'

export class StorefrontQuickOrderPage extends AbstractPage {
  protected PAGE_URL = Cypress.env('STOREFRONT_URL') + '/en/quick-order'

  getQuickOrderForm = (): Cypress.Chainable => {
    return cy.get('[data-qa="component quick-order-form"]')
  }

  getQuickOrderRows = (): Cypress.Chainable => {
    return this.getQuickOrderForm().find(
      '[data-qa="component quick-order-row"]'
    )
  }

  getProductSearchAutocompleteFields = (): Cypress.Chainable => {
    return cy.get('[data-qa="component product-search-autocomplete-form"]')
  }

  getSuggestedProductsList(): Cypress.Chainable {
    return cy.get('[data-qa="component products-list"]')
  }

  searchProduct = (
    fieldIndex: number,
    skuOrName: string
  ): Cypress.Chainable => {
    return this.getProductSearchAutocompleteFields()
      .eq(fieldIndex)
      .type(skuOrName)
  }

  applySuggestedProduct = (skuOrName: string): Cypress.Chainable => {
    // Wait for suggestions to appear and contain the expected text, then click.
    return this.getSuggestedProductsList()
      .filter(':visible')
      .should('contain', skuOrName, { timeout: 20000 })
      .contains(skuOrName, { timeout: 20000 })
      .click()
  }

  selectProductMerchant = (
    fieldIndex: number,
    merchant: string
  ): Cypress.Chainable => {
    return this.getQuickOrderRows()
      .eq(fieldIndex)
      .find('[data-qa="component custom-select"] select')
      .should('contain', merchant)
      .select(merchant, { force: true })
  }

  getQuantityInput = (rowIndex: number): Cypress.Chainable => {
    return this.getQuickOrderRows()
      .eq(rowIndex)
      .find('#quick_order_item_embedded_form_quantity')
  }

  incrementQuantity = (rowIndex: number): Cypress.Chainable => {
    return this.getQuickOrderRows()
      .eq(rowIndex)
      .find('[data-qa="quantity-counter__button--increment"]')
      .click()
  }

  decrementQuantity = (rowIndex: number): Cypress.Chainable => {
    return this.getQuickOrderRows()
      .eq(rowIndex)
      .find('[data-qa="quantity-counter__button--decrement"]')
      .click()
  }

  removeProduct = (rowIndex: number): Cypress.Chainable => {
    return cy
      .get('.js-quick-order-form__remove-row-trigger')
      .eq(rowIndex)
      .click()
  }

  getMerchantFilterSelect = (): Cypress.Chainable => {
    return this.getQuickOrderForm()
      .find('[data-qa="component custom-select"] select')
      .first()
  }

  selectMerchant = (merchant: string, maxRetries = 3): void => {
    const trySelectMerchant = (retries = 0): void => {
      this.getMerchantFilterSelect().then(($select) => {
        const hasOption = $select
          .find('option')
          .toArray()
          .some((option) => option.textContent?.trim() === merchant)

        if (hasOption) {
          cy.wrap($select).select(merchant, { force: true })
          return
        }

        if (retries >= maxRetries) {
          throw new Error(
            `Merchant "${merchant}" not found in the quick-order merchant filter after ${maxRetries} reload(s) — the merchant search index may not have finished syncing.`
          )
        }

        cy.log(
          `Merchant "${merchant}" not found in filter yet. Reloading... [${retries + 1}/${maxRetries}]`
        )
        cy.reload()
        cy.wait(5000)
        trySelectMerchant(retries + 1)
      })
    }

    trySelectMerchant()
  }

  addToCart = (): Cypress.Chainable => {
    return cy.get('[name="addToCart"]').click()
  }

  createOrder = (): Cypress.Chainable => {
    return cy.get('[name="createOrder"]').click()
  }
}
