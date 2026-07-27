// ***********************************************************
// This support/e2e.ts is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

import './cy-commands/storefront/utility-commands'
import './cy-commands/storefront/cart-commands'
import './cy-commands/glue/checkout-commands'
import './cy-commands/backoffice/oms-transition-commands'
import './cy-commands/glue/addresses-commands'
import './cy-commands/glue/carts-commands'
import 'cypress-plugin-api'
import 'cypress-mochawesome-reporter/register'

// ***********************************************************
// Example global intercept to prevent logging requests containing 'Google' or 'YouTube'
// beforeEach(() => {
//   cy.intercept(
//     {
//       url: /.*(google|youtube).*/,
//     },
//     {
//       log: false,
//     }
//   )
// })
// ***********************************************************

export const isDocker = (): boolean => {
  return Cypress.env('docker')
}

export const isLocal = (): boolean => {
  return Cypress.env('environment').endsWith('local')
}

export const isCI = (): boolean => {
  return Cypress.env('environment').endsWith('ci')
}
