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
