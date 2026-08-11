import { AbstractPage } from '../../abstract-page'

const loginForm = 'form[name="auth"]'

export class BackofficeLoginPage extends AbstractPage {
  protected PAGE_URL = Cypress.env('BACK_OFFICE_URL') + '/security-gui/login'

  getEmailField = (): Cypress.Chainable => {
    return cy.get(loginForm).find('#auth_username')
  }

  getPasswordField = (): Cypress.Chainable => {
    return cy.get(loginForm).find('#auth_password')
  }

  login = (email: string, password: string): Cypress.Chainable => {
    cy.clearAllCookies()
    this.visit()
    this.getEmailField().clear()
    this.getEmailField().type(email)
    this.getPasswordField().clear()
    this.getPasswordField().type(password)

    return cy.get(loginForm).submit()
  }
}
