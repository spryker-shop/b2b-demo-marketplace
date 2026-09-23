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

import { dirname, parse, relative } from 'path'
import './cy-commands/storefront/utility-commands'
import './cy-commands/storefront/cart-commands'
import './cy-commands/glue/checkout-commands'
import './cy-commands/backoffice/oms-transition-commands'
import './cy-commands/glue/addresses-commands'
import './cy-commands/glue/carts-commands'
import './cy-commands/dynamic-fixtures/dynamic-fixtures-commands'
import 'cypress-plugin-api'
import 'cypress-mochawesome-reporter/register'

// Every spec can own a pair of fixture files, resolved from its own path so nothing has
// to be registered anywhere. For cypress/e2e/storefront/cart/storefront-cart-smoke.cy.ts
// those are:
//
//   cypress/fixtures/storefront/cart/dynamic-storefront-cart-smoke.json
//   cypress/fixtures/storefront/cart/static-storefront-cart-smoke.json
//
// The dynamic one is a DynamicFixtures payload: the data it describes is created in the
// shop before the spec runs, and the created records land in Cypress.env('dynamicFixtures').
// The static one holds values that come from project configuration rather than demodata
// (payment method names, for instance) and lands in Cypress.env('staticFixtures').
// Both are optional — specs that still use the shared @fixtures/*.json files are untouched.
before(() => {
  const fixturePath = getSpecFixturePath()

  cy.task(
    'isFileExists',
    `${Cypress.config('fixturesFolder')}/${fixturePath.staticFixtures}.json`
  ).then((isFileExists) => {
    if (isFileExists) {
      cy.loadStaticFixturesByPath(fixturePath.staticFixtures).then(
        (staticFixtures) => {
          Cypress.env('staticFixtures', staticFixtures)
        }
      )
    }
  })

  cy.task(
    'isFileExists',
    `${Cypress.config('fixturesFolder')}/${fixturePath.dynamicFixtures}.json`
  ).then((isFileExists) => {
    if (isFileExists) {
      cy.loadDynamicFixturesByPayload(fixturePath.dynamicFixtures).then(
        (dynamicFixtures) => {
          Cypress.env('dynamicFixtures', dynamicFixtures)
        }
      )
    }
  })
})

const getSpecFixturePath = (): Record<string, string> => {
  const relativePath = relative('cypress/e2e/', Cypress.spec.relative)
  const directory = dirname(relativePath)
  const specName = parse(relativePath).name.replace('.cy', '')

  return {
    dynamicFixtures: `${directory}/dynamic-${specName}`,
    staticFixtures: `${directory}/static-${specName}`,
  }
}

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
