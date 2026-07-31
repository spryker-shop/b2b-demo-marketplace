// Dynamic fixtures replace hardcoded demodata (SKUs, customer emails, store-prefixed
// references) with data created on the fly through the Testify DynamicFixtures API
// (`POST <glue-backend>/dynamic-fixtures`, provided by spryker/testify-backend-api and
// registered in this project via DynamicFixturesBackendResourcePlugin).
//
// A payload is a list of operations; each operation calls a Codeception helper method
// from tests/PyzTest/Zed/TestifyBackendApi/codeception.dynamic.fixtures.yml and stores
// its result under `key`, so later operations can reference it as `#key` / `#key.field`.

// Values that depend on how the project is configured (which stores exist, which locale
// and currency they use, the default password) must never be repeated inside payloads.
// They are written as `{{ENV_VAR}}` placeholders and resolved from the environment files
// in .envs/ — that is the single place to change when the store set changes.
const interpolateEnvPlaceholders = <T>(fixtures: T): T => {
  const serialized = JSON.stringify(fixtures)

  const resolved = serialized.replace(
    /\{\{([A-Z0-9_]+)\}\}/g,
    (_match, variableName: string) => {
      const value = Cypress.env(variableName)

      if (value === undefined || value === null || value === '') {
        throw new Error(
          `Fixture placeholder {{${variableName}}} could not be resolved: environment variable ${variableName} is not set. Add it to .envs/.env.<environment>.`
        )
      }

      return String(value)
    }
  )

  return JSON.parse(resolved)
}

// The API answers with a JSON:API document: a single resource object when the payload
// produced one keyed result, an array of them otherwise. Flatten both shapes into a
// plain `{ key: data }` object so specs can read `dynamicFixtures.customer.email`.
const mapResponseToFixtures = (
  responseBody: Record<string, any>
): Record<string, unknown> => {
  if (Array.isArray(responseBody.data)) {
    return responseBody.data.reduce(
      (
        fixtures: Record<string, unknown>,
        resource: { attributes: { key: string; data: unknown } }
      ) => {
        fixtures[resource.attributes.key] = resource.attributes.data

        return fixtures
      },
      {}
    )
  }

  return {
    [responseBody.data.attributes.key]: responseBody.data.attributes.data,
  }
}

Cypress.Commands.add(
  'loadDynamicFixturesByPayload',
  function loadDynamicFixturesByPayload(
    dynamicFixturesFilePath: string,
    retries = 2
  ): Cypress.Chainable {
    return cy.fixture(dynamicFixturesFilePath).then((payload) => {
      return cy
        .request({
          method: 'POST',
          url: Cypress.env('GLUE_BACKEND_URL') + '/dynamic-fixtures',
          headers: {
            'Content-Type': 'application/vnd.api+json',
          },
          body: interpolateEnvPlaceholders(payload),
          // creating products, publishing them to the search engine and waiting for the
          // queue workers to catch up takes far longer than a normal API call
          timeout: 300000,
          failOnStatusCode: false,
        })
        .then((response) => {
          // 500/408 here are usually a queue worker or publish-and-sync timeout rather
          // than a broken payload, so give it another go before failing the whole spec
          if (response.status === 500 || response.status === 408) {
            if (retries > 0) {
              cy.log('Retrying dynamic fixtures request after error or timeout')

              return cy.loadDynamicFixturesByPayload(
                dynamicFixturesFilePath,
                retries - 1
              )
            }

            throw new Error(
              `Dynamic fixtures request failed with status ${response.status}: ${JSON.stringify(response.body)}`
            )
          }

          if (!response.isOkStatusCode) {
            throw new Error(
              `Dynamic fixtures request failed with status ${response.status}: ${JSON.stringify(response.body)}`
            )
          }

          return mapResponseToFixtures(response.body)
        })
    })
  }
)

Cypress.Commands.add(
  'loadStaticFixturesByPath',
  (staticFixturesFilePath: string): Cypress.Chainable => {
    return cy
      .fixture(staticFixturesFilePath)
      .then((staticFixtures) => interpolateEnvPlaceholders(staticFixtures))
  }
)
