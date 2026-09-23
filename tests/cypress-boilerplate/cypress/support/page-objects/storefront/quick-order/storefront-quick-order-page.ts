import { AbstractPage } from '../../abstract-page'

export class StorefrontQuickOrderPage extends AbstractPage {
  protected PAGE_URL =
    Cypress.env('STOREFRONT_URL') +
    '/' +
    Cypress.env('LOCALE_PREFIX') +
    '/quick-order'

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

  getSuggestedProductsList(timeout?: number): Cypress.Chainable {
    return cy.get('[data-qa="component products-list"]', { timeout })
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
    // Wait for suggestions to appear and contain the expected text, then click. The retry
    // window has to be set on the `cy.get()` inside getSuggestedProductsList(): an options
    // object passed to `should('contain', value, …)` is ignored, so this used to fall back
    // to the 4s default. A product created per run by dynamic fixtures needs longer than
    // that to become searchable.
    return this.getSuggestedProductsList(20000)
      .filter(':visible', { timeout: 20000 })
      .should('contain', skuOrName)
      .contains(skuOrName)
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
      .then(($select) => {
        const value = $select
          .find('option')
          .filter((_index, option) => option.textContent?.trim() === merchant)
          .first()
          .val() as string

        return cy.wrap($select).select(value, { force: true })
      })
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
        const availableOptions = $select
          .find('option')
          .toArray()
          .map((option: { textContent: string }) => option.textContent?.trim())

        const hasOption = availableOptions.some(
          (optionText: string) => optionText === merchant
        )

        if (hasOption) {
          cy.wrap($select).select(merchant, { force: true })
          return
        }

        const optionsSummary = `[${availableOptions.join(', ')}]`

        if (retries >= maxRetries) {
          throw new Error(
            `Merchant "${merchant}" not found in the quick-order merchant filter after ${maxRetries} reload(s) — the merchant search index may not have finished syncing. Options present at last attempt: ${optionsSummary}`
          )
        }

        cy.log(
          `Merchant "${merchant}" not found in filter yet. Options present: ${optionsSummary}. Reloading... [${retries + 1}/${maxRetries}]`
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
