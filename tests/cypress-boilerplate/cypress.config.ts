import { defineConfig } from 'cypress'
import * as fs from 'fs'
import { createHash, randomBytes } from 'crypto'
import { execFileSync } from 'child_process'
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
        // Enables the MCP Commerce Server feature flag, which ships fail-closed and is therefore OFF
        // on a freshly built environment. The value is written into the key-value entry the Glue read
        // path resolves: no console command sets a Configuration Management value, and writing MySQL
        // alone would not propagate without a publish worker.
        //
        // Uses execFileSync with an argument array (no shell string) so nothing has to survive two
        // layers of quoting. Returns the exit code; the spec asserts it is 0 rather than letting a
        // silent failure surface later as a confusing 404.
        enableMcpCommerceServer: (): number => {
          const settingKey = 'mcp_commerce:server:general:is_enabled'
          const storageKey = 'kv:configuration:global'
          const script = [
            `raw=$(redis-cli -h key_value_store -n 1 --raw GET ${storageKey})`,
            `printf '%s' "$raw" > /tmp/mcp-kv.json`,
            `python3 -c "import json;p='/tmp/mcp-kv.json';d=json.load(open(p));d['${settingKey}']='true';open(p,'w').write(json.dumps(d,separators=(',',':')))"`,
            `redis-cli -h key_value_store -n 1 -x SET ${storageKey} < /tmp/mcp-kv.json`,
          ].join(' && ')

          try {
            execFileSync('docker/sdk', ['cli', script], {
              cwd: process.env.PROJECT_LOCATION ?? '../..',
              stdio: 'pipe',
              timeout: 180000,
            })

            return 0
          } catch (error) {
            const failure = error as {
              status?: number
              stderr?: Buffer
              stdout?: Buffer
            }
            console.error(
              'enableMcpCommerceServer failed:',
              failure.stderr?.toString() ??
                failure.stdout?.toString() ??
                String(error)
            )

            return failure.status ?? 1
          }
        },
        // PKCE S256 needs a real SHA-256. `window.crypto.subtle` is unavailable in the spec because
        // the app is served over plain HTTP and Web Crypto requires a secure context, so the pair is
        // generated in Node where `crypto` is always present.
        createPkcePair: (): { codeVerifier: string; codeChallenge: string } => {
          const toBase64Url = (buffer: Buffer): string =>
            buffer
              .toString('base64')
              .replace(/\+/g, '-')
              .replace(/\//g, '_')
              .replace(/=+$/, '')

          const codeVerifier = toBase64Url(randomBytes(32))
          const codeChallenge = toBase64Url(
            createHash('sha256').update(codeVerifier).digest()
          )

          return { codeVerifier, codeChallenge }
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
