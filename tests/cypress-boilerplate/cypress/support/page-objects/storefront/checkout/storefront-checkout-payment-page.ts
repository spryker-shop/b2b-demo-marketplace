import { AbstractPage } from '../../abstract-page'

const paymentForm = 'form[name="paymentForm"]'

export class StorefrontCheckoutPaymentPage extends AbstractPage {
  protected PAGE_URL =
    Cypress.env('STOREFRONT_URL') +
    '/' +
    Cypress.env('LOCALE_PREFIX') +
    '/checkout/payment'

  selectPaymentMethod = (paymentMethodKey: string): void => {
    cy.get(paymentForm)
      .find(
        `input[name="paymentForm[paymentSelection]"][value="${paymentMethodKey}"]`
      )
      .check({ force: true })
  }

  submitPayment = (): void => {
    cy.get(paymentForm).submit()
  }

  providePayment = (paymentName: string): void => {
    this.selectPaymentMethod(paymentName)
    this.submitPayment()
  }
}
