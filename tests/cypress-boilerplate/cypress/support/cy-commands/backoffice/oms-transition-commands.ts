import userCredentials from '../../../fixtures/user-data.json'
import { BackofficeLoginPage } from '../../page-objects/backoffice/login/backoffice-login-page'
import { BackofficeOrderListPage } from '../../page-objects/backoffice/order-management/backoffice-order-list-page'
import { BackofficeOrderDetailsPage } from '../../page-objects/backoffice/order-management/backoffice-order-details-page'
import { isCI, isDocker, isLocal } from '../../e2e'

const backofficeLoginPage = new BackofficeLoginPage()
const backofficeOrderListPage = new BackofficeOrderListPage()
const backofficeOrderDetailsPage = new BackofficeOrderDetailsPage()

Cypress.Commands.add(
  'triggerOmsTransition',
  (path = Cypress.env('PROJECT_LOCATION')): Cypress.Chainable => {
    if (isLocal() || isDocker() || isCI()) {
      if (isDocker()) {
        // Execute curl commands for Docker environment
        const dockerCliUrl = Cypress.env('DOCKER_CLI_URL')
        const checkConditionRequest = {
          method: 'POST',
          url: `${dockerCliUrl}/console`,
          body: "APPLICATION_STORE='DE' COMMAND='console oms:check-condition' cli.sh",
        }
        const checkTimeoutRequest = {
          method: 'POST',
          url: `${dockerCliUrl}/console`,
          body: "APPLICATION_STORE='DE' COMMAND='console oms:check-timeout' cli.sh",
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
          })
          .then((result) => {
            expect(
              result.code,
              `Command "${baseCommand} console oms:check-condition" failed with code ${result.code}. Output: ${result.stdout}. Error: ${result.stderr}`
            ).to.eq(0)
          })
          .then(() => {
            return cy
              .exec(`${baseCommand} console oms:check-timeout`, {
                failOnNonZeroExit: true,
              })
              .then((result) => {
                expect(
                  result.code,
                  `Command "${baseCommand} console oms:check-timeout" failed with code ${result.code}. Output: ${result.stdout}. Error: ${result.stderr}`
                ).to.eq(0)
              })
          })
      } else {
        // keep in mind that by default exec() command runs commands in the root Cypress tests directly
        // please provide the correct path to your Spryker env in 'PROJECT_LOCATION' env variable
        // and change the validation logic, as default is set not fail on non-zero exit
        const baseCommand = path ? `cd ${path} && docker/sdk` : 'docker/sdk'

        return cy
          .exec(`${baseCommand} console oms:check-condition`, {
            failOnNonZeroExit: false,
          })
          .then((result) => {
            expect(
              result.code,
              `Command "${baseCommand} console oms:check-condition" failed with code ${result.code}. Output: ${result.stdout}. Error: ${result.stderr}`
            ).to.not.eq(0)
          })
          .then(() => {
            return cy
              .exec(`${baseCommand} console oms:check-timeout`, {
                failOnNonZeroExit: false,
              })
              .then((result) => {
                expect(
                  result.code,
                  `Command "${baseCommand} console oms:check-timeout" failed with code ${result.code}. Output: ${result.stdout}. Error: ${result.stderr}`
                ).to.not.eq(0)
              })
          })
      }
    } else {
      return cy.wrap(null)
    }
  }
)

Cypress.Commands.add(
  'triggerOmsEvent',
  (orderReference: string, eventName: string): Cypress.Chainable => {
    return backofficeLoginPage
      .login(
        userCredentials.backofficeUser.email,
        userCredentials.backofficeUser.password
      )
      .then(() => backofficeOrderListPage.visit())
      .then(() => backofficeOrderListPage.viewOrderByReference(orderReference))
      .then(() => backofficeOrderDetailsPage.triggerOms(eventName))
  }
)

Cypress.Commands.add(
  'waitForOrderProcessing',
  (desiredStatus: string, maxRetries: number): Cypress.Chainable => {
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
)
