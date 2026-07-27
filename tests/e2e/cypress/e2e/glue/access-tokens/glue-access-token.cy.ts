import { validateSchema } from '@support/api-helper/api-helper'
import accessTokenSchema from '@support/glue-endpoints/authentication/access-tokens-response'
import { AccessTokens } from '@support/glue-endpoints/authentication/access-tokens'
import customerCredentials from '@fixtures/customer-data.json'
import errorResponseSchema from '@support/api-helper/general-responses/error-response'

const tokenEndpoint = new AccessTokens()

context('Access Token + Examples of schema validation', () => {
  it('Positive | Can get access token via GLue', () => {
    tokenEndpoint
      .getCustomerAccessToken(
        customerCredentials.email,
        customerCredentials.password
      )
      .then((response) => {
        validateSchema(accessTokenSchema, response)
        expect(response.isOkStatusCode).to.be.true
        expect(
          response.body.data.attributes.expiresIn,
          'expiresIn is greater than 0'
        ).to.be.gt(0)
      })
  })
  it('Negative | Failed authentication via GLue', () => {
    tokenEndpoint
      .getCustomerAccessToken(
        'fake_email@spryker.com',
        customerCredentials.password
      )
      .then((response) => {
        validateSchema(errorResponseSchema, response)
        expect(response.isOkStatusCode).not.to.be.true
        expect(response.body.errors[0].detail).to.be.eq(
          'Failed to authenticate user.'
        )
      })
  })
})
