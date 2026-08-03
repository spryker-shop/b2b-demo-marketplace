import { AbstractPage } from '../../abstract-page'

const paymentForm = 'form[name="paymentForm"]'

export class StorefrontCheckoutPaymentPage extends AbstractPage {
  protected PAGE_URL = Cypress.env('STOREFRONT_URL') + '/en/checkout/payment'

  selectPaymentMethod = (paymentName: string): void => {
    cy.get(paymentForm).contains(paymentName).click()
  }

  submitPayment = (): void => {
    cy.get(paymentForm).submit()
  }

  providePayment = (paymentName: string): void => {
    this.selectPaymentMethod(paymentName)
    this.submitPayment()
  }
}
