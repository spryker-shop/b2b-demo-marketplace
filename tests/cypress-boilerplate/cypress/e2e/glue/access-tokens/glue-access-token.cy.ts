// The customer this spec authenticates as is created before it runs by
// cypress/fixtures/glue/access-tokens/dynamic-glue-access-token.json, so the spec no longer
// depends on a demodata customer existing with a known password.
import { validateSchema } from '@support/api-helper/api-helper'
import accessTokenSchema from '@support/glue-endpoints/authentication/access-tokens-response'
import { AccessTokens } from '@support/glue-endpoints/authentication/access-tokens'
import errorResponseSchema from '@support/api-helper/general-responses/error-response'
import { getFixtures } from '@support/fixture-helper/fixture-helper'
import {
  AccessTokenDynamicFixtures,
  AccessTokenStaticFixtures,
} from '@support/types/glue'

const tokenEndpoint = new AccessTokens()

let dynamicFixtures: AccessTokenDynamicFixtures
let staticFixtures: AccessTokenStaticFixtures

context('Access Token + Examples of schema validation', () => {
  before(() => {
    ;({ dynamicFixtures, staticFixtures } = getFixtures<
      AccessTokenDynamicFixtures,
      AccessTokenStaticFixtures
    >())
  })

  it('Positive | Can get access token via GLue', () => {
    tokenEndpoint
      .getCustomerAccessToken(
        dynamicFixtures.customer.email,
        staticFixtures.defaultPassword
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
        staticFixtures.defaultPassword
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
