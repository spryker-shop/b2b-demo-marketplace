import { AccessTokens } from '../../glue-endpoints/authentication/access-tokens'

const tokenEndpoint = new AccessTokens()

export class GlueAddressesScenarios {
  /**
   * Deletes all customer addresses via the Glue API.
   *
   * @example
   * const glueAddressesScenarios = new GlueAddressesScenarios()
   * glueAddressesScenarios.deleteAllCustomerAddresses('sonia@spryker.com', 'change123', 'DE-21')
   *   .then(() => {
   *     cy.log('All customer addresses deleted.');
   *   });
   */
  deleteAllCustomerAddresses = (
    email: string,
    password: string,
    customerReference: string
  ): Cypress.Chainable => {
    let token: string

    return cy.wrap(null).then(() => {
      return tokenEndpoint
        .getCustomerAccessToken(email, password)
        .then((response) => {
          token = response.body.data.attributes.accessToken
          return token
        })
        .then((token) => {
          // Step 1: Get the list of all addresses
          return cy
            .api({
              method: 'GET',
              url: `${Cypress.env('GLUE_URL')}/customers/${customerReference}/addresses`,
              headers: {
                Authorization: `Bearer ${token}`,
              },
              failOnStatusCode: false,
            })
            .then((response) => {
              expect(response.isOkStatusCode).to.be.true
              if (response.status === 200 && response.body.data.length > 0) {
                const addresses = response.body.data
                cy.log(
                  `${addresses.length} addresses found. They will be deleted.`
                )

                let deleteCount = 0

                const deletePromises = addresses.map((address) => {
                  const addressUuid = address.id

                  // Step 2: Delete all addresses
                  return cy
                    .api({
                      method: 'DELETE',
                      url: `${Cypress.env('GLUE_URL')}/customers/${customerReference}/addresses/${addressUuid}`,
                      headers: {
                        Authorization: `Bearer ${token}`,
                      },
                      failOnStatusCode: false,
                    })
                    .then((deleteResponse) => {
                      if (deleteResponse.status === 204) {
                        deleteCount++
                      }
                      expect(deleteResponse.status).to.eq(204)
                    })
                })

                return Cypress.Promise.all(deletePromises).then(() => {
                  cy.log(`${deleteCount} addresses were deleted.`)
                })
              } else {
                cy.log('No addresses found for the customer.')
              }
            })
        })
    })
  }
}
