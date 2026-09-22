import { BackofficeLoginPage } from '../../page-objects/backoffice/login/backoffice-login-page'
import { BackofficeOrderListPage } from '../../page-objects/backoffice/order-management/backoffice-order-list-page'
import { BackofficeOrderDetailsPage } from '../../page-objects/backoffice/order-management/backoffice-order-details-page'
import { isCI, isDocker, isLocal } from '../../e2e'

const backofficeLoginPage = new BackofficeLoginPage()
const backofficeOrderListPage = new BackofficeOrderListPage()
const backofficeOrderDetailsPage = new BackofficeOrderDetailsPage()

export class OmsTransitionScenarios {
  /**
   * Triggers OMS transition commands via CLI.
   *
   * @example
   * const omsTransitionScenarios = new OmsTransitionScenarios()
   * omsTransitionScenarios.triggerOmsTransition('path/to/env')
   *   .then(() => {
   *     cy.log('OMS transition triggered.')
   *   })
   */
  triggerOmsTransition = (
    path = Cypress.env('PROJECT_LOCATION')
  ): Cypress.Chainable => {
    if (isLocal() || isDocker() || isCI()) {
      if (isDocker()) {
        // Execute curl commands for Docker environment
        const dockerCliUrl = Cypress.env('DOCKER_CLI_URL')
        const checkConditionRequest = {
          method: 'POST',
          url: `${dockerCliUrl}/console`,
          body: `APPLICATION_STORE='${Cypress.env('STORE_NAME')}' COMMAND='console oms:check-condition' cli.sh`,
        }
        const checkTimeoutRequest = {
          method: 'POST',
          url: `${dockerCliUrl}/console`,
          body: `APPLICATION_STORE='${Cypress.env('STORE_NAME')}' COMMAND='console oms:check-timeout' cli.sh`,
        }

        return cy
          .api(checkConditionRequest)
          .then((response) => {
            expect(
              response.status,
              `Request "${JSON.stringify(checkConditionRequest)}" failed with status ${response.status}. Response: ${response.body}`
            ).to.eq(200)
          })
          .then(() => {
            return cy.api(checkTimeoutRequest).then((response) => {
              expect(
                response.status,
                `Request "${JSON.stringify(checkTimeoutRequest)}" failed with status ${response.status}. Response: ${response.body}`
              ).to.eq(200)
            })
          })
      } else if (isCI()) {
        // These commands will fail, if the CI workflow does not include a valid authentication for docker/sdk cli commands
        const baseCommand = path ? `cd ${path} && docker/sdk` : 'docker/sdk'

        return cy
          .exec(`${baseCommand} console oms:check-condition`, {
            failOnNonZeroExit: true,
            timeout: 300000,
          })
          .then((result) => {
            cy.log(
              `oms:check-condition exited with code ${result.code}. Output: ${result.stdout}`
            )
          })
          .then(() => {
            return cy
              .exec(`${baseCommand} console oms:check-timeout`, {
                failOnNonZeroExit: true,
                timeout: 300000,
              })
              .then((result) => {
                cy.log(
                  `oms:check-timeout exited with code ${result.code}. Output: ${result.stdout}`
                )
              })
          })
      } else {
        const baseCommand = path ? `cd ${path} && docker/sdk` : 'docker/sdk'

        return cy
          .exec(`${baseCommand} console oms:check-condition`, {
            failOnNonZeroExit: false,
          })
          .then((result) => {
            expect(
              result.code ?? 0,
              `Command "${baseCommand} console oms:check-condition" LOCAL failed with code ${result.code}. Output: ${result.stdout}. Error: ${result.stderr}`
            ).to.eq(0)
          })
          .then(() => {
            return cy
              .exec(`${baseCommand} console oms:check-timeout`, {
                failOnNonZeroExit: false,
              })
              .then((result) => {
                expect(
                  result.code ?? 0,
                  `Command "${baseCommand} console oms:check-timeout" failed with code ${result.code}. Output: ${result.stdout}. Error: ${result.stderr}`
                ).to.eq(0)
              })
          })
      }
    } else {
      return cy.wrap(null)
    }
  }

  /**
   * Wait for an OMS trigger button to appear on the Backoffice order details page.
   *
   * Looks for a button whose visible text contains `triggerName`. If not found,
   * the page is reloaded and the check is retried up to `maxRetries` times,
   * waiting 10s between attempts. Resolves when the trigger is found or throws
   * an Error after exhausting retries.
   *
   * @param triggerName - visible text of the trigger to wait for
   * @param maxRetries - number of reload attempts before failing (default: 2)
   *
   * Notes: uses jQuery `:contains()` (case-sensitive substring match) and performs
   * full page reloads; consider polling an API instead of reloads if needed.
   */
  private waitForOmsTrigger = (
    triggerName: string,
    maxRetries = 2
  ): Cypress.Chainable => {
    let retries = 0

    const tryFindTrigger = (): Cypress.Chainable => {
      return backofficeOrderDetailsPage.getOmsTriggers().then(($triggers) => {
        const match = $triggers.find(`button:contains("${triggerName}")`)

        if (match.length > 0) {
          return cy.wrap(null)
        }

        const availableTriggers = $triggers
          .find('button')
          .toArray()
          .map((button: HTMLButtonElement) => button.innerText.trim())
        const triggersSummary = `[${availableTriggers.join(', ')}]`

        if (retries >= maxRetries) {
          throw new Error(
            `Trigger "${triggerName}" not found after ${maxRetries} reload(s). Triggers present at last attempt: ${triggersSummary}`
          )
        }

        retries++
        cy.log(
          `Trigger "${triggerName}" not found. Triggers present: ${triggersSummary}. Reloading... [${retries}/${maxRetries}]`
        )
        return cy.reload().then(() => {
          cy.wait(10000)
          return tryFindTrigger()
        })
      })
    }

    return tryFindTrigger()
  }

  /**
   * Triggers an OMS event for a given order through the UI in back office.
   *
   * @example
   * const omsTransitionScenarios = new OmsTransitionScenarios()
   * omsTransitionScenarios.triggerOmsEvent('DE-1', 'Pay')
   *   .then(() => {
   *     cy.log('OMS event triggered.')
   *   })
   */
  triggerOmsEvent = (
    orderReference: string,
    eventName: string,
    maxRetries: number,
    email: string,
    password: string
  ): Cypress.Chainable => {
    return backofficeLoginPage
      .login(email, password)
      .then(() => backofficeOrderListPage.visit())
      .then(() => backofficeOrderListPage.viewOrderByReference(orderReference))
      .then(() => this.waitForOmsTrigger(eventName, maxRetries)) // try reload up to 2 times
      .then(() => backofficeOrderDetailsPage.triggerOms(eventName))
  }

  /**
   * Waits for an order to process to a desired status. Back office UI.
   *
   * @example
   * const omsTransitionScenarios = new OmsTransitionScenarios()
   * omsTransitionScenarios.waitForOrderProcessing('sent to merchant', 10)
   *   .then(() => {
   *     cy.log('Order reached desired status.')
   *   })
   */
  waitForOrderProcessing = (
    desiredStatus: string,
    maxRetries: number
  ): Cypress.Chainable => {
    function findStatusWithText(
      desiredStatus: string,
      retries = 0
    ): Cypress.Chainable {
      if (retries >= maxRetries) {
        throw new Error(
          `Max retries reached while looking for order trigger with text "${desiredStatus}"`
        )
      }
      // Search for the link with the specified order status
      return cy.get('a').then(($links) => {
        const matchingStatus = $links.filter((index, lnk) => {
          return lnk.innerText.includes(desiredStatus)
        })

        if (matchingStatus.length > 0) {
          // Status found, you can perform other tasks or assertions here if needed
          cy.log(`Status "${desiredStatus}" was found`)
          return cy.wrap(matchingStatus) // Return a Cypress.Chainable
        } else {
          // Status not found, reload and try again
          return cy.reload().then(() => {
            cy.wait(10000)
            return findStatusWithText(desiredStatus, retries + 1)
          })
        }
      })
    }

    return findStatusWithText(desiredStatus)
  }
}
