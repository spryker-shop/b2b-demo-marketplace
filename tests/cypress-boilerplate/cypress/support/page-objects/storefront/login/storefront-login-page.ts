import { AbstractPage } from '../../abstract-page'

const loginForm = 'form[name="loginForm"]'

export class StorefrontLoginPage extends AbstractPage {
  protected PAGE_URL = Cypress.env('STOREFRONT_URL') + '/en/login'

  getEmailField = (): Cypress.Chainable => {
    return cy.get(loginForm).find('#loginForm_email')
  }

  getPasswordField = (): Cypress.Chainable => {
    return cy.get(loginForm).find('#loginForm_password')
  }

  login = (email: string, password: string): void => {
    cy.clearAllCookies()
    this.visit()
    this.getEmailField().clear()
    this.getEmailField().type(email)
    this.getPasswordField().clear()
    this.getPasswordField().type(password)
    cy.get(loginForm).submit()
  }
}
