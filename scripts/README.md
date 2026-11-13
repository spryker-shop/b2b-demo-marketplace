# Environment-provided configuration

How a configuration value (for example the GA4 Measurement ID) gets from an
environment variable into the storefront.

Ticket: CC-40468.

---

## 1. Why a bridge is needed

Spryker's Configuration module **never reads environment variables**. There is no
`getenv()` anywhere in `vendor/spryker/configuration/src/`. A value only reaches
the storefront through this chain:

```
environment variable
   |   scripts/import-local-configuration.sh      (this bridge)
   v
spy_configuration_value        (database)
   |   publish queue
   v
spy_configuration_storage      (database)
   |   sync.storage.configuration queue
   v
kv:configuration:global        (Redis)
   |
   v
configurationValue() in Twig -> storefront
```

---

## 2. The naming convention

**The environment variable name is the setting key, verbatim.**

The script imports every variable whose name matches `^[a-z0-9_]+(:[a-z0-9_]+)+$`
- lowercase segments joined by colons.

## 3. Add a setting to an environment

### Local

`deploy.dev.yml`, under `image: environment:`

```yaml
environment:
    integrations:google_analytics:tracking:measurement_id: G-XXXXXXXXXX
```

Then `docker/sdk boot && docker/sdk up` so the container picks up the variable.

### Cloud / SE environments

Set the same variable name in the environment's own configuration (AWS). Nothing
is committed - the repository never carries an environment's values, so
installing this boilerplate gives a customer no Spryker configuration.

---

## 4. What happens on install

`config/install/destructive.yml`, section `demodata`:

```yaml
import-environment-configuration:
    command: 'bash scripts/import-local-configuration.sh'
```

The script:

1. Scans `env` for setting-key-shaped variable names.
2. Writes the matches to `data/import/common/common/configuration_value.local.csv`
   at **global** scope. That file is gitignored (`/data/import/**/*.local.csv`)
   and is generated fresh on every run.
3. Runs `data:import --config=data/import/local/configuration_value_local.yml`.

With no matching variables it prints `No configuration settings found in the
environment - nothing to import.` and exits 0 - so a customer install is a no-op.

---

## 5. Run it manually

The variables live **inside the container**, not on the host. Running the script
from your own shell will always report "nothing to import".

```bash
docker/sdk cli "bash scripts/import-local-configuration.sh"
```

## 6. Verify

Back Office: **Configuration -> Integrations -> Google Analytics**. The page
defaults to **global** scope; a value stored at store scope will not appear there
unless you switch the scope selector.
