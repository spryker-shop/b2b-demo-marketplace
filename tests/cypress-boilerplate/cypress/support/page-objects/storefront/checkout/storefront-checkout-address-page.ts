import { AbstractPage } from '../../abstract-page'

const addressForm = 'form[name="addressesForm"]'

export class StorefrontCheckoutAddressPage extends AbstractPage {
  protected PAGE_URL = Cypress.env('STOREFRONT_URL') + '/en/checkout/address'

  getShippingAddressDropdown = (): Cypress.Chainable => {
    // this checkout defaults to its per-item shipping address flow; a top-level
    // "single address" selector also exists in the DOM but stays hidden, so filter to
    // the ones actually visible rather than relying on raw index
    return cy
      .get(addressForm)
      .find('[data-qa*="checkout-full-addresses"]')
      .filter(':visible')
      .first()
  }

  getSalutationField = (): Cypress.Chainable => {
    return cy.get(addressForm).find('#addressesForm_shippingAddress_salutation')
  }

  getFirstNameField = (): Cypress.Chainable => {
    return cy.get(addressForm).find('#addressesForm_shippingAddress_first_name')
  }

  getLastNameField = (): Cypress.Chainable => {
    return cy.get(addressForm).find('#addressesForm_shippingAddress_last_name')
  }

  getCompanyField = (): Cypress.Chainable => {
    return cy.get(addressForm).find('#addressesForm_shippingAddress_company')
  }

  getStreetField = (): Cypress.Chainable => {
    return cy.get(addressForm).find('#addressesForm_shippingAddress_address1')
  }

  getNumberField = (): Cypress.Chainable => {
    return cy.get(addressForm).find('#addressesForm_shippingAddress_address2')
  }

  getAdditionalAddressField = (): Cypress.Chainable => {
    return cy.get(addressForm).find('#addressesForm_shippingAddress_address3')
  }

  getCityField = (): Cypress.Chainable => {
    return cy.get(addressForm).find('#addressesForm_shippingAddress_city')
  }

  getCountryField = (): Cypress.Chainable => {
    return cy.get(addressForm).find('#addressesForm_shippingAddress_iso2_code')
  }

  getPhoneField = (): Cypress.Chainable => {
    return cy.get(addressForm).find('#addressesForm_shippingAddress_phone')
  }

  getSaveAddressCheckbox = (): Cypress.Chainable => {
    return cy
      .get(addressForm)
      .find('#addressesForm_shippingAddress_isAddressSavingSkipped')
  }

  getBillingTheSameAsShippingCheckbox = (): Cypress.Chainable => {
    // the wrapping <toggler-checkbox> custom element shares the same id as its inner
    // <input> — scope to the input tag so cy.get doesn't resolve to the wrapper
    return cy.get(addressForm).find('input#addressesForm_billingSameAsShipping')
  }

  getBillingAddressDropdown = (): Cypress.Chainable => {
    return cy
      .get(addressForm)
      .find('[data-qa="checkout-full-addresses"]')
      .filter(':visible')
      .eq(1)
  }

  selectFirstBusinessAddressAvailableForShipping = (): void => {
    this.getShippingAddressDropdown().click()
    cy.get('[id*="company_business_unit_address"]').first().click()
  }

  submitAddress = (): void => {
    cy.get(addressForm).submit()
  }

  provideExistingAddress = (): void => {
    this.selectFirstBusinessAddressAvailableForShipping()
    // billing defaults to "Define new address" with no fields filled in, which fails
    // server-side validation and silently re-renders the same address step; check this
    // instead of also picking a billing address
    this.getBillingTheSameAsShippingCheckbox().check({ force: true })
    this.submitAddress()
  }
}
