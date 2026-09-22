import { defineConfig } from 'cypress'
import * as fs from 'fs'
import { config as dotenvConfig } from 'dotenv'

interface ResolvedStore {
  name: string
  default_locale_iso_code: string
  default_currency_iso_code: string
  countries: string[]
}

const resolveStoreContext = async (
  env: Record<string, string>
): Promise<Record<string, string>> => {
  const endpoint = `${env.GLUE_BACKEND_URL}/dynamic-fixtures`
  const response = await fetch(endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/vnd.api+json' },
    body: JSON.stringify({
      data: {
        type: 'dynamic-fixtures',
        attributes: {
          synchronize: false,
          operations: [
            { type: 'helper', name: 'getAllowedStore', key: 'store' },
          ],
        },
      },
    }),
  })

  if (!response.ok) {
    throw new Error(
      `Could not resolve the store context from ${endpoint} (${response.status}). The same endpoint creates every fixture, so no spec can run without it.`
    )
  }

  const store: ResolvedStore = (await response.json()).data.attributes.data

  if (!store?.name) {
    throw new Error(`${endpoint} returned no store - this shop has none.`)
  }

  return {
    STORE_NAME: store.name,
    LOCALE_NAME: store.default_locale_iso_code,
    LOCALE_PREFIX: store.default_locale_iso_code.split('_')[0].toLowerCase(),
    CURRENCY_CODE: store.default_currency_iso_code,
    COUNTRY_ISO2: store.countries[0],
  }
}

export default defineConfig({
  watchForFileChanges: false,
  screenshotOnRunFailure: true,
  trashAssetsBeforeRuns: true,
  chromeWebSecurity: false,
  video: false,
  downloadsFolder: 'cypress/data/downloads',
  fixturesFolder: 'cypress/fixtures',
  screenshotsFolder: 'cypress/data/screenshots',
  videosFolder: 'cypress/data/videos',
  supportFolder: 'cypress/support',
  experimentalModifyObstructiveThirdPartyCode: true,
  experimentalMemoryManagement: true,
  pageLoadTimeout: 60000,
  viewportWidth: 1280,
  viewportHeight: 800,
  reporter: 'cypress-mochawesome-reporter',
  reporterOptions: {
    charts: true,
    reportTitle: 'mochawesome-report',
    // reportFilename: '[status]_[datetime]-report',
    embeddedScreenshots: true,
    inlineAssets: true,
    saveAllAttempts: false,
    reportDir: 'cypress/data/reports',
    showSkipped: true,
  },
  e2e: {
    supportFile: 'cypress/support/e2e.ts',

    setupNodeEvents: async (on, config) => {
      const plugin = await import('cypress-mochawesome-reporter/plugin')
      plugin.default(on)
      on('task', {
        // used by support/e2e.ts to detect whether the current spec ships its own
        // dynamic/static fixture files (cy.fixture would fail the spec on a missing file)
        isFileExists: (filePath: string): boolean => {
          return fs.existsSync(filePath)
        },
      })
      //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      // ENVIRONMENT SETUP
      //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      // Use local as the default environment to run E2E tests on
      let environment = 'local'

      // When the environment is set, use this environment. This can be set via the command line `npx cypress run --env environment=staging`
      // Possible options are: ci, local, testing, staging, production
      const environments = ['ci', 'local', 'testing', 'staging', 'production']

      // Check if the environment is defined in Cypress environment configuration
      if (typeof config.env.environment !== 'undefined') {
        environment = config.env.environment
      } else {
        // If not defined, set it to default
        config.env.environment = environment
      }

      if (!environments.includes(environment)) {
        throw new Error(
          `Invalid environment: ${environment}, allowed environments are: ${environments.join(', ')}`
        )
      }

      //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      // ENVIRONMENT VARIABLES (local)
      //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      const envPath = process.cwd() + '/.env'
      if (fs.existsSync(envPath)) {
        const envVars = dotenvConfig({
          path: envPath,
        })

        if (envVars.error) {
          throw envVars.error
        }

        // Iterate over each var and pass it to config.env use the key as key and the value as value
        for (const key in envVars.parsed) {
          config.env[key] = envVars.parsed[key]
        }
      }

      //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      // ENVIRONMENT VARIABLES (for environment e.g. staging)
      //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      // Define the environment file to use
      const envFileName = process.cwd() + `/.envs/.env.${environment}`
      if (fs.existsSync(envFileName)) {
        const result = dotenvConfig({ path: envFileName })

        if (result.error) {
          throw result.error
        }

        // Iterate over each var and pass it to config.env use the key as key and the value as value
        for (const key in result.parsed) {
          if (!(key in config.env)) {
            config.env[key] = result.parsed[key]
          }
        }
      }

      // Set GLUE_URL as baseUrl from the loaded environment variables
      config.baseUrl = config.env.GLUE_URL

      //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      // STORE CONTEXT (resolved from the shop - never configured)
      //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
      Object.assign(config.env, await resolveStoreContext(config.env))

      return config
    },
  },
  retries: {
    // Retry attempts for `cypress run`
    runMode: 2,
    // Retry attempts for `cypress open`
    openMode: 0,
  },

  env: {
    // default environment is used as a fallback
    DEFAULT_ENVIRONMENT: 'local',
  },
})
