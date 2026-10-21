# Marketplace Uninstall Script - Maintenance Guide

## Overview

The marketplace uninstall system consists of three main components:

1. **uninstall-marketplace-config.json** - Centralized configuration file containing all cleanup recipes
2. **uninstall-marketplace-modules.sh** - Shell script that reads the configuration and executes backend cleanup operations
3. **uninstall-frontend-marketplace.sh** - Shell script that removes marketplace-specific frontend files and dependencies

This architecture separates configuration from execution logic, making it easier to maintain and extend the uninstall process.

The project state the scripts produce is called **B2B mode**: B2B Demo Marketplace without the Merchant Portal and the
multi-merchant features. CI builds B2B mode on every pull request (`.github/workflows/_cypress.yml`, `b2b` cells, and
`.github/workflows/_robot-api.yml`, "Robot / API B2B" job), so any change to marketplace wiring in the project must keep
the configuration in sync. See [Verifying a Change](#verifying-a-change) for what those CI jobs do not cover.

## File Structure

```
project-root/
├── uninstall-marketplace-config.json       # Configuration file
├── uninstall-marketplace-modules.sh        # Backend cleanup script
├── uninstall-frontend-marketplace.sh       # Frontend cleanup script
├── UNINSTALL_MAINTENANCE_GUIDE.md          # This guide
└── data/import/
    ├── b2b_common/                         # B2B mode demo data (the scripts switch the install recipes to it)
    ├── b2b_common/minimal.yml              # B2B mode minimal import (config/install/docker.ci.api.yml)
    ├── b2b_robot/                          # B2B mode Robot Framework demo data
    └── local/b2b_full_EU.yml, b2b_full_ROBOT.yml
```

The `data/import/b2b_*` files are part of the repository in both modes; they are only *used* once the scripts have run.

## Configuration File Structure

The `uninstall-marketplace-config.json` file contains the following top-level sections, listed in the order the backend
script processes them:

| Section | Step | Purpose |
|---|---|---|
| — (hardcoded in the script) | 1–2 | `MARKETPLACE_FEATURES` and `MARKETPLACE_CORE_MODULES` removed with `composer remove` |
| `php_dependency_providers` | 3 | Operations on PHP files (plugins, methods, constants, use statements) |
| `config_files` | 4 | Operations on configuration files (`config/Shared/*.php`, bundles, install recipes, templates) |
| `directories_to_remove` | 5 | Directories deleted with `rm -rf` |
| `files_to_remove` | 5 | Single files deleted |
| `import_entities_to_remove` + `import_yaml_files` | 6 | `data_entity` blocks removed from the marketplace import YAML files |
| `docker_commands_to_remove` + `docker_config_files` | 7 | Command blocks removed from install recipes |
| `docker_config_substitutions` + `docker_config_files` | 8 | Literal string replacements in install recipes (marketplace → B2B data import paths) |
| `deploy_files` | 9 | `merchant-portal` applications, and `entry-point: MerchantPortal` endpoints declared under other applications, removed from deploy files |
| `xml_files` | 10 | Operations on XML files (OMS, Back Office navigation) |
| `test_files` | 11 | Operations on tests and test fixtures |
| `payment_config_updates` | 12 | Re-adds the B2B `DummyPayment` provider (B2B-MP ships only `DummyMarketplacePayment`) |

Steps 3, 4, 10, 11 and 12 share the same operation engine and the same operation types. A file may appear in several
sections; it is processed once per section, in the step order above.

```json
{
  "php_dependency_providers": {},
  "config_files": {},
  "import_entities_to_remove": [],
  "import_yaml_files": [],
  "docker_commands_to_remove": [],
  "docker_config_files": [],
  "deploy_files": [],
  "docker_config_substitutions": {},
  "directories_to_remove": [],
  "files_to_remove": [],
  "xml_files": {},
  "test_files": {},
  "payment_config_updates": {}
}
```

## Supported Operation Types

### 1. PHP Dependency Provider Operations

Location in JSON: `php_dependency_providers`

Each PHP file can have multiple operations.

Every operation must change the file. When an operation matches nothing, the script prints
`⚠ No match in <file>: <operation>` — the configuration has drifted from the code (the plugin was renamed, moved to
another file or removed). Fix the configuration; do not ignore the warning. `ensure_use_statement` is the only exception,
because it is idempotent by design.

Removal operations remove the whole line when the removed item (plugin, array value, `add()` call, constant, method)
occupies its own lines, and fall back to an in-place removal otherwise. This keeps neighbouring statements on separate
lines, so the result passes the project's code sniffer.

#### Operation: `remove_use`
Removes use statement (import) for a class. Matches both `use Vendor\...\ExamplePlugin;` and aliased imports
(`use Vendor\...\HeadersSecurityEventDispatcherPlugin as MerchantPortalHeadersSecurityEventDispatcherPlugin;`) — for an
aliased import, pass the alias as `class_name`.

```json
{
  "type": "remove_use",
  "class_name": "ExamplePlugin"
}
```

#### Operation: `remove_plugin`
Removes plugin instantiation from arrays and its use statement.

```json
{
  "src/Pyz/Zed/Example/ExampleDependencyProvider.php": {
    "operations": [
      {
        "type": "remove_plugin",
        "plugin_class": "ExamplePlugin"
      }
    ],
    "success_messages": [
      "✓ Removed ExamplePlugin"
    ]
  }
}
```

#### Operation: `remove_event_subscriber`
Removes event subscriber `add()` calls.

```json
{
  "type": "remove_event_subscriber",
  "subscriber_class": "ExampleEventSubscriber"
}
```

#### Operation: `remove_collection_add`
Removes collection `add()` method calls.

```json
{
  "type": "remove_collection_add",
  "plugin_class": "ExampleCollectionPlugin"
}
```

#### Operation: `remove_resource_relationship`
Removes REST API resource relationships.

```json
{
  "type": "remove_resource_relationship",
  "resource_type": "ResourcesRestApi::RESOURCE_EXAMPLE",
  "plugin_class": "ExampleResourceRelationshipPlugin"
}
```

#### Operation: `remove_method`
Removes an entire method, including its docblock and attributes when present. The method body is matched by brace
counting, so nesting depth does not matter. Methods without a docblock (typed signatures) are supported.

```json
{
  "type": "remove_method",
  "method_name": "getExamplePlugins"
}
```

#### Operation: `remove_constant`
Removes a constant with its docblock (when present). Typed constants (`protected const string NAME = ...;`) are
supported. The whole lines are removed, together with the blank line left behind.

```json
{
  "type": "remove_constant",
  "constant_name": "EXAMPLE_CONSTANT"
}
```

#### Operation: `remove_array_entry`
Removes a `'key' => [ ... ],` array entry by key (whole lines).

```json
{
  "type": "remove_array_entry",
  "key": "example-key@domain.com"
}
```

#### Operation: `remove_queue_entry`
Removes queue configuration entries.

```json
{
  "type": "remove_queue_entry",
  "queue_config_key": "ExampleConfig::EXAMPLE_QUEUE"
}
```

#### Operation: `remove_array_constant_entry`
Removes array entries that are constant references.

```json
{
  "type": "remove_array_constant_entry",
  "constant_reference": "ExampleConfig::EXAMPLE_VALUE"
}
```

#### Operation: `remove_data_import_console`
Removes DataImportConsole instantiations.

```json
{
  "type": "remove_data_import_console",
  "config_constant": "ExampleDataImportConfig::IMPORT_TYPE_EXAMPLE"
}
```

#### Operation: `remove_array_value_entry`
Removes array value entries.

```json
{
  "type": "remove_array_value_entry",
  "array_value": "ExampleConfig::EXAMPLE_TYPE"
}
```

#### Operation: `remove_line_containing`
Removes lines containing a specific pattern.

```json
{
  "type": "remove_line_containing",
  "pattern": "ExampleText"
}
```

#### Operation: `replace_string`
Replaces one string with another.

```json
{
  "type": "replace_string",
  "old_value": "OldValue",
  "new_value": "NewValue"
}
```

#### Operation: `ensure_use_statement`
Ensures a use statement exists (adds if missing).

```json
{
  "type": "ensure_use_statement",
  "class_name": "ExampleClass",
  "after_pattern": "use Spryker\\Zed\\Kernel"
}
```

#### Operation: `regex_replace`
Performs regex-based string replacement.

```json
{
  "type": "regex_replace",
  "pattern": "regex_pattern",
  "replacement": "replacement_text",
  "flags": "MULTILINE|DOTALL"
}
```

#### Operation: `replace_or_add_constant`
Replaces an existing constant or adds it if it doesn't exist.

```json
{
  "type": "replace_or_add_constant",
  "constant_pattern": "EXAMPLE_CONSTANT",
  "new_definition": "public const EXAMPLE_CONSTANT = 'value';"
}
```

### 2. Configuration File Operations

Location in JSON: `config_files`

Same operation types as PHP files, plus:

#### Operation: `remove_config_assignment`
Removes config assignment lines.

```json
{
  "config/Shared/config_default.php": {
    "operations": [
      {
        "type": "remove_config_assignment",
        "constant_pattern": "ExampleConstants::EXAMPLE_KEY"
      }
    ],
    "success_messages": [
      "✓ Removed example configuration"
    ]
  }
}
```

#### Operation: `remove_array_value`
Removes array values from configuration arrays.

```json
{
  "type": "remove_array_value",
  "array_value": "ExampleDependencyProvider"
}
```

#### Operation: `remove_filesystem_config`
Removes a `'name' => [ ... ],` filesystem configuration block (whole lines). The block must not contain nested arrays.
For one-line assignments such as `$config[...]['name'] = $localConfig;` use `remove_line_containing`.

```json
{
  "type": "remove_filesystem_config",
  "filesystem_name": "example-filesystem"
}
```

#### Operation: `remove_section_comment`
Removes section comments.

```json
{
  "type": "remove_section_comment",
  "comment_pattern": "Example Section Comment"
}
```

#### Operation: `remove_array_key_value`
Removes array key-value pairs.

```json
{
  "type": "remove_array_key_value",
  "key_pattern": "example-key"
}
```

#### Operation: `replace_array_config_content`
Replaces the entire content of a configuration array.

```json
{
  "type": "replace_array_config_content",
  "config_key": "ARRAY_KEY",
  "new_content": "'new' => 'value'"
}
```

### 3. XML File Operations

Location in JSON: `xml_files`

#### Operation: `remove_xml_element`
Removes an XML element. `element_pattern` is a **literal** string (not a regex), matched exactly, including line breaks
and the indentation of every line after the first. When the element occupies whole lines, those lines are removed;
otherwise the matched text is removed in place.

```json
{
  "type": "remove_xml_element",
  "element_pattern": "<process>DummyMerchantCommission</process>"
}
```

Multi-line example (Back Office navigation entry):

```json
{
  "type": "remove_xml_element",
  "element_pattern": "<merchant-commission-gui>\n                <label>Merchant Commissions</label>\n                ...\n            </merchant-commission-gui>"
}
```

#### Operation: `add_xml_transition`
Adds an XML transition after a specific pattern.

```json
{
  "type": "add_xml_transition",
  "after_pattern": "<transition.*?/>",
  "transition": "<transition>...</transition>"
}
```

### 4. Test File Operations

Location in JSON: `test_files`

#### Operation: `add_skip_annotation`
Adds @skip annotation to test methods or classes.

```json
{
  "type": "add_skip_annotation",
  "method_name": "testExampleMethod",
  "class_name": "ExampleTest"
}
```

### 5. Simple Array Entries

#### Import Entities
Lists of import entity names to remove from YAML files.

```json
"import_entities_to_remove": [
  "entity-name-1",
  "entity-name-2"
]
```

#### Docker Commands
Lists of docker commands to remove from configuration.

```json
"docker_commands_to_remove": [
  "command-name-1",
  "command-name-2"
]
```

#### Docker Config Substitutions
Literal replacements applied to every file in `docker_config_files` (Step 8). Used to point the install recipes at the
B2B demo data. A substitution is reported only for the files that contain the old value.

```json
"docker_config_substitutions": {
  "data:import --config=data/import/local/full_EU.yml": "data:import --config=data/import/local/b2b_full_EU.yml"
}
```

The B2B dataset is EU-only (DE and AT stores), so every regional marketplace import (`full_${SPRYKER_REGION}`,
`full_${SPRYKER_CURRENT_REGION}`, `full_EU`) is switched to `b2b_full_EU.yml`. A substitution target must exist in the
repository.

### 6. Directory and File Removal

Format: `"path:DisplayName"`

```json
"directories_to_remove": [
  "src/Pyz/Zed/Example:Example Module",
  "tests/PyzTest/Zed/Example:Example Tests"
],
"files_to_remove": [
  "config/Zed/navigation-main-merchant-portal.xml:Merchant Portal main navigation"
]
```

Remove a project module directory when its core module is uninstalled (even indirectly, see
[Case 11](#case-11-a-plugin-whose-package-is-removed-indirectly)): a `src/Pyz` class that extends a missing core class
breaks PHPStan for the whole project, and a test directory for a removed module breaks its Codeception suite.

## How to Add New Cases

### Case 1: Removing a Plugin from a Dependency Provider

**Scenario**: You need to remove `NewMarketplacePlugin` from `ExampleDependencyProvider.php`

1. Open `uninstall-marketplace-config.json`
2. Add or update the file entry:

```json
"src/Pyz/Zed/Example/ExampleDependencyProvider.php": {
  "operations": [
    {
      "type": "remove_plugin",
      "plugin_class": "NewMarketplacePlugin"
    }
  ],
  "success_messages": [
    "✓ Removed NewMarketplacePlugin from Example"
  ]
}
```

### Case 2: Removing a Configuration Value

**Scenario**: Remove a configuration assignment from `config_default.php`

```json
"config/Shared/config_default.php": {
  "operations": [
    {
      "type": "remove_config_assignment",
      "constant_pattern": "NewConstants::NEW_CONFIG_KEY"
    }
  ],
  "success_messages": [
    "✓ Removed new configuration from config_default.php"
  ]
}
```

### Case 3: Removing an Import Entity

**Scenario**: A new merchant-related import entity needs to be removed

1. Add to the `import_entities_to_remove` array:

```json
"import_entities_to_remove": [
  "existing-entity-1",
  "existing-entity-2",
  "new-merchant-entity"
]
```

2. Ensure the YAML files are listed in `import_yaml_files`:

```json
"import_yaml_files": [
  "data/import/local/full_EU.yml",
  "data/import/local/full_US.yml"
]
```

### Case 4: Removing a Module Directory

**Scenario**: Remove a new marketplace module

```json
"directories_to_remove": [
  "src/Pyz/Zed/NewMarketplaceModule:NewMarketplaceModule",
  "tests/PyzTest/Zed/NewMarketplaceModule:NewMarketplaceModule Tests"
]
```

### Case 5: Removing Multiple Plugins from One File

**Scenario**: Multiple plugins need to be removed from the same file

```json
"src/Pyz/Zed/Example/ExampleDependencyProvider.php": {
  "operations": [
    {
      "type": "remove_plugin",
      "plugin_class": "FirstPlugin"
    },
    {
      "type": "remove_plugin",
      "plugin_class": "SecondPlugin"
    },
    {
      "type": "remove_method",
      "method_name": "getMarketplacePlugins"
    }
  ],
  "success_messages": [
    "✓ Removed FirstPlugin from Example",
    "✓ Removed SecondPlugin from Example",
    "✓ Removed getMarketplacePlugins method"
  ]
}
```

### Case 6: Removing Docker Commands

**Scenario**: A new marketplace-specific docker command needs removal

```json
"docker_commands_to_remove": [
  "existing-command",
  "new-marketplace-command"
]
```

And ensure the docker config files are listed:

```json
"docker_config_files": [
  "config/install/docker.yml",
  "config/install/docker.ci.acceptance.yml"
]
```

### Case 7: Removing XML Elements

**Scenario**: Remove marketplace-specific XML transitions from OMS configuration

```json
"config/Zed/oms/MarketplacePayment01.xml": {
  "operations": [
    {
      "type": "remove_xml_element",
      "element_pattern": "<transition.*?event=\"send merchant order mail\".*?</transition>"
    }
  ],
  "success_messages": [
    "✓ Removed merchant transitions from OMS"
  ]
}
```

### Case 8: Adding Skip Annotations to Tests

**Scenario**: Skip marketplace-related tests

```json
"tests/PyzTest/Glue/Checkout/_support/CheckoutApiTester.php": {
  "operations": [
    {
      "type": "add_skip_annotation",
      "method_name": "testMerchantCheckout"
    }
  ],
  "success_messages": [
    "✓ Added skip annotation to merchant tests"
  ]
}
```

### Case 9: Replacing Configuration with Regex

**Scenario**: Update payment configuration to remove marketplace payment methods

```json
"src/Pyz/Glue/PaymentsRestApi/PaymentsRestApiConfig.php": {
  "operations": [
    {
      "type": "regex_replace",
      "pattern": "DummyMarketplacePaymentInvoice.*?,",
      "replacement": "",
      "flags": "MULTILINE"
    }
  ],
  "success_messages": [
    "✓ Removed marketplace payment methods"
  ]
}
```

### Case 10: Ensuring Use Statements

**Scenario**: After removing a plugin, ensure a replacement plugin's use statement exists

```json
"src/Pyz/Zed/Example/ExampleDependencyProvider.php": {
  "operations": [
    {
      "type": "remove_plugin",
      "plugin_class": "MarketplacePlugin"
    },
    {
      "type": "ensure_use_statement",
      "class_name": "StandardPlugin",
      "after_pattern": "use Spryker\\\\Zed\\\\Kernel"
    }
  ],
  "success_messages": [
    "✓ Replaced MarketplacePlugin with StandardPlugin"
  ]
}
```

### Case 11: A Plugin Whose Package Is Removed Indirectly

`composer remove` also removes every package that only the removed features required. A plugin from such a package can
still be wired in the project, which compiles but fails at runtime with `Class not found` the first time the stack is
built. Example: `spryker/merchant-user-password-reset-mail` disappears together with the Merchant Portal features, so
`MailMerchantUserPasswordResetRequestStrategyPlugin` had to be removed from `UserPasswordResetDependencyProvider`, or the
Back Office "Forgot password" flow broke in B2B mode.

To find the full package list, run the scripts in a scratch worktree (see [Verifying a Change](#verifying-a-change)) and
diff `composer.lock`:

```bash
git show HEAD:composer.lock > /tmp/before.lock
python3 -c "import json;n=lambda f:{p['name'] for p in json.load(open(f))['packages']};print(*sorted(n('/tmp/before.lock')-n('composer.lock')),sep='\n')"
```

### Case 12: Marketplace Wiring in a Non-PHP File

Back Office navigation, deploy files and install recipes are configuration too:

- Back Office menu entries → `xml_files` on `config/Zed/navigation.xml` with `remove_xml_element`. Entries whose module
  is uninstalled are hidden at runtime by `BackofficeNavigationItemCollectionFilterPlugin`, but entries of modules that
  stay installed (for example Merchant Registrations) stay visible.
- A new deploy file with a `merchant-portal` application → `deploy_files`.
- A new install recipe running Merchant Portal or ACL entity commands, or importing marketplace demo data →
  `docker_config_files`. When a command is the only entry of its section, remove the whole section with a
  `regex_replace` in `config_files` (see `config/install/production.yml`), so no empty section is left behind.

## Best Practices

### 1. Testing Changes

Before running the script:
- Commit your changes to git
- Test on a separate branch or a scratch worktree (see [Verifying a Change](#verifying-a-change))
- Verify the configuration JSON is valid:
  ```bash
  python3 -m json.tool uninstall-marketplace-config.json
  ```

### 2. Success Messages

Always provide clear success messages:
```json
"success_messages": [
  "✓ Removed SpecificPlugin from ModuleName",
  "✓ Removed marketplace configuration"
]
```

### 3. Grouping Related Operations

Keep related operations in the same file entry:
```json
"src/Pyz/Zed/Example/ExampleDependencyProvider.php": {
  "operations": [
    {"type": "remove_plugin", "plugin_class": "Plugin1"},
    {"type": "remove_plugin", "plugin_class": "Plugin2"},
    {"type": "remove_method", "method_name": "getRelatedPlugins"}
  ]
}
```

### 4. Maintaining Operation Order

Operations are executed in the order they appear. If order matters (e.g., removing a method that contains plugins), list operations accordingly.

### 5. File Path Accuracy

Always use exact file paths:
- ✅ `src/Pyz/Zed/Example/ExampleDependencyProvider.php`
- ❌ `src/Pyz/Zed/Example/ExampleDependencyProvider`

### 6. Class Name Precision

Use exact class names without namespace:
- ✅ `"plugin_class": "ExamplePlugin"`
- ❌ `"plugin_class": "Spryker\\Zed\\Example\\ExamplePlugin"`

## Troubleshooting

### Issue: "⚠ No match in &lt;file&gt;: &lt;operation&gt;"

**Solution**: The operation targets code that is no longer there. Find where the class, method or line moved
(`grep -rn ExamplePlugin src/`) and update the entry, or delete the operation if the marketplace wiring is gone. Common
causes: the plugin moved to another dependency provider, an import is aliased (`remove_use` needs the alias), or a REST
resource moved to API Platform (the whole `GlueApplicationDependencyProvider` recipe became obsolete this way).

### Issue: "File not found" error

**Solution**: Verify the file path in the configuration matches the actual file location.

### Issue: Plugin not removed

**Solution**: 
- Check the exact class name in the file
- Verify the operation type is correct
- Check if the plugin is instantiated differently (e.g., `::class` vs `new`)

### Issue: JSON syntax error

**Solution**: 
- Validate JSON: `python3 -m json.tool uninstall-marketplace-config.json`
- Check for missing commas, brackets, or quotes
- Ensure no trailing commas in arrays

### Issue: Script fails mid-execution

**Solution**:
- Check the script output for the specific step that failed
- Review the configuration for that step
- Ensure all file paths exist
- Check file permissions

## Extending the Script

To add new operation types:

1. Add the operation handler to the `marketplace_cleanup.py` heredoc in `uninstall-marketplace-modules.sh` (the script
   writes it to `/tmp/marketplace_cleanup.py` at runtime)
2. Update the `process_file()` function to handle the new operation type
3. Document the new operation type in this guide

Prefer the `cut_lines()` / `cut_all_matches()` helpers for anything that removes whole lines: they also drop the blank
line the removal leaves behind, so the result passes the code sniffer.

Example:
```python
def remove_new_pattern(content, pattern_value):
    """Remove new pattern type."""
    pattern = rf'your_regex_pattern_here'
    content = re.sub(pattern, '', content)
    return content
```

Then add to the operation processor:
```python
elif op_type == 'remove_new_pattern':
    content = remove_new_pattern(content, operation['pattern_value'])
```

## Running the Scripts

### Backend Uninstall

```bash
# Make script executable (first time only)
chmod +x uninstall-marketplace-modules.sh

# Run the backend uninstall script
./uninstall-marketplace-modules.sh
```

This script will:
- Remove marketplace Composer packages
- Clean up PHP dependency providers
- Remove marketplace-specific configuration
- Remove marketplace directories and files
- Clean up data import configurations
- Update install recipes (Merchant Portal and ACL entity commands, B2B data import paths)
- Remove the Merchant Portal application from deploy files
- Update OMS, Back Office navigation, tests and payment configuration

### Frontend Uninstall

```bash
# Make script executable (first time only)
chmod +x uninstall-frontend-marketplace.sh

# Run the frontend uninstall script
./uninstall-frontend-marketplace.sh
```

This script will:
- Remove the `mp:*` scripts from `package.json`
- Remove the Merchant Portal steps (`mp-zed-ui`, `mp:update:paths`) from the `postinstall` script

See [Frontend Uninstall Script](#frontend-uninstall-script) for why nothing else is needed.

### Recommended Execution Order

1. Run the frontend uninstall script first:
   ```bash
   ./uninstall-frontend-marketplace.sh
   ```

2. Then run the backend uninstall script:
   ```bash
   ./uninstall-marketplace-modules.sh
   ```
   The frontend script must run first: it strips the `postinstall` steps of the `mp-zed-ui` workspace, which the backend
   script removes with `spryker/zed-ui`. CI uses the same order.

3. Review all changes before committing:
   ```bash
   git status
   git diff
   ```

## Support

For questions or issues with the uninstall script:

1. Review this guide
2. Check the script output for specific errors
3. Validate the JSON configuration
4. Review recent changes to the configuration file
5. Consult with the development team

## Frontend Uninstall Script

The Merchant Portal build tooling (Angular workspace, lint and TypeScript configuration) ships inside `spryker/zed-ui` as
the `mp-zed-ui` npm workspace. The project's `package.json` picks it up through the `vendor/spryker/*` workspace glob, so
the workspace disappears when the backend script removes `spryker/zed-ui` with Composer.

`uninstall-frontend-marketplace.sh` therefore only undoes what the project itself declares in `package.json`:

- All `mp:*` scripts
- The `postinstall` steps that reference `mp:update:paths` or `mp-zed-ui`. The remaining steps (`update:config` for the
  Zed and Yves builders) must stay: they generate the TypeScript configuration the Zed and Yves builds read. Leaving the
  Merchant Portal steps behind makes every later `npm ci` fail on a workspace that no longer exists.

The script does not run `npm install`; `docker/sdk up` rebuilds the frontend.

## What B2B Mode Keeps on Purpose

B2B mode is **not** identical to B2B Demo Shop. It removes the Merchant Portal and the multi-merchant features, but keeps
the following, because the B2B mode demo data and features need them:

| Kept | Why |
|---|---|
| `spryker-feature/marketplace-product-offer`, `marketplace-product-offer-prices`, `marketplace-inventory-management` | The B2B dataset (`data/import/b2b_common`) imports product offers, offer prices, offer stock and merchant stock. All offers belong to the single merchant `MERSPRYKER` and back Self-Service Portal services and click & collect. |
| `spryker-feature/marketplace-merchant`, `marketplace-product`, `marketplace-shipment` | Required by the features above and by merchant relationships (B2B contracts). Their storefront entry points (merchant pages, merchant registration form) are unwired by the configuration. |
| Product offer, service point and shipment type wiring (widgets, Back Office "Offers") | Self-Service Portal and click & collect. |
| Merchants, merchant relationships, merchant relation requests | B2B contract features, also part of B2B Demo Shop. |

Everything Merchant Portal-specific must be removed: applications, routers, security, ACL rules, navigation, console
commands, audit logging, filesystems, OMS processes, data import entities and project modules.

Known limitation: the B2B dataset is EU-only, so B2B mode imports `b2b_full_EU.yml` regardless of region.

## Verifying a Change

The CI jobs that run B2B mode (Cypress `b2b` cells, Robot "API B2B") exercise the storefront and Glue. They do **not**
run static analysis or Codeception in B2B mode, nor do they cover the Back Office password reset, the cloud install
recipes (`production.yml`, `destructive.yml`, `dynamic-store.yml`) or the non-default deploy files. After changing the
configuration, verify locally in a scratch worktree so the main checkout stays untouched:

```bash
git worktree add --detach ../b2b-mode HEAD
cp uninstall-marketplace-config.json uninstall-marketplace-modules.sh uninstall-frontend-marketplace.sh ../b2b-mode/
cp -cR vendor ../b2b-mode/vendor        # APFS clone on macOS; use cp -R elsewhere
cd ../b2b-mode
./uninstall-frontend-marketplace.sh && ./uninstall-marketplace-modules.sh 2>&1 | tee uninstall.log
```

1. **No warnings.** `grep '⚠' uninstall.log` must return nothing. Every warning is configuration drift.
2. **No syntax errors.** `git diff --name-only --diff-filter=d -- '*.php' | xargs -n1 php -l`
3. **PHPStan on the touched files.** Copy the generated code first (`cp -cR ../b2b-demo-marketplace/src/{Generated,Orm} src/`),
   then run `vendor/bin/phpstan analyze -c phpstan.neon $(git diff --name-only --diff-filter=d -- 'src/*.php')`. A `src/Pyz` class that
   extends a removed core class aborts PHPStan with an internal error; remove that module directory.
4. **Code sniffer on the touched files.**
   `vendor/bin/phpcs --standard=phpcs.xml --no-cache $(git diff --name-only --diff-filter=d -- 'src/*.php')` must report nothing.
   Use `--no-cache`: a cached run from the marketplace checkout can hide violations. On PHP 8.5, add
   `-d error_reporting='E_ALL & ~E_DEPRECATED'` to `php`, otherwise a Slevomat sniff aborts with a deprecation notice.
5. **No orphaned project modules.** Every `src/Pyz/<Layer>/<Module>` must still have a core module of the same name in
   `vendor/`, unless it is a project-only module.
6. **No Merchant Portal leftovers.**
   `grep -rIil "merchant-portal\|merchantportal\|mportal" src config deploy*.yml --exclude-dir=Generated --exclude-dir=Orm`
   should list only `config/install/ci.restore-db.stage2.yml`. That recipe restores the shared marketplace database dump
   in CI and is never used in B2B mode, so it is intentionally not processed.
7. **CI.** Open the pull request and check the Cypress `b2b` and Robot "API B2B" jobs.

Remove the worktree afterwards: `git worktree remove --force ../b2b-mode`.

## Changelog

Document significant changes to the configuration:

```
2026-10-21: CC-41086 B2B mode audit
            - Fixed: Back Office password reset and the product offer stock table failed with "Class not found"
              (plugins from packages removed indirectly)
            - Fixed: production/development/testing install recipes ran removed acl-entity console commands
            - Fixed: data/import/b2b_common/minimal.yml was referenced but missing (added)
            - Fixed: Glue PaymentsRestApiConfig / CheckoutRestApiConfig kept marketplace payment keys or a wrong
              required-fields structure
            - Added: deploy files, install recipes and the CURRENT_REGION import substitution that were not processed
            - Added: removal of Merchant Portal console commands, audit logging, ACL whitelist, filesystems,
              navigation, OMS processes and orphaned project modules
            - Added: files_to_remove section; "⚠ No match" warning for operations that change nothing
            - Changed: remove_use supports aliases; remove_method / remove_constant support code without docblocks
              and typed constants; all removal operations work line-based, so B2B mode passes the code sniffer;
              Step 9 also removes MerchantPortal endpoints declared under other applications
            - Removed: obsolete GlueApplicationDependencyProvider recipe (resources moved to API Platform) and other
              operations that no longer matched
2026-02-05: Updated maintenance guide with all operation types and new sections
2026-02-05: Added frontend uninstall script documentation
2026-02-02: Initial configuration file created with all marketplace features
```

## Complete Operation Type Reference

### PHP and Config File Operations
- `remove_use` - Remove use/import statements
- `remove_plugin` - Remove plugin instantiation and use statement
- `remove_event_subscriber` - Remove event subscriber add() calls
- `remove_collection_add` - Remove collection add() calls
- `remove_resource_relationship` - Remove REST API resource relationships
- `remove_method` - Remove entire methods with docblocks
- `remove_constant` - Remove constants with docblocks
- `remove_array_entry` - Remove array entries by key
- `remove_queue_entry` - Remove queue configuration entries
- `remove_array_constant_entry` - Remove array constant references
- `remove_data_import_console` - Remove DataImportConsole instantiations
- `remove_array_value_entry` - Remove array value entries
- `remove_config_assignment` - Remove config assignment lines
- `remove_array_value` - Remove array values
- `remove_filesystem_config` - Remove filesystem configuration blocks
- `remove_section_comment` - Remove section comments
- `remove_array_key_value` - Remove array key-value pairs
- `remove_line_containing` - Remove lines containing specific patterns
- `replace_string` - Replace one string with another
- `ensure_use_statement` - Add use statement if missing
- `regex_replace` - Regex-based string replacement
- `replace_or_add_constant` - Replace or add constant definitions
- `replace_array_config_content` - Replace configuration array content

### XML File Operations
- `remove_xml_element` - Remove XML elements by pattern
- `add_xml_transition` - Add XML transitions after pattern

### Test File Operations
- `add_skip_annotation` - Add @skip annotations to tests

### Top-Level Configuration Sections
- `php_dependency_providers` - PHP dependency provider files
- `config_files` - Configuration files
- `xml_files` - XML configuration files
- `test_files` - Test files
- `payment_config_updates` - Payment configuration updates
  (B2B-MP ships only `DummyMarketplacePayment`; this section re-adds the B2B `DummyPayment` provider:
  config wiring, `DummyPaymentCheckoutPreConditionPlugin`, `payment.twig` icon block, `payment_method*.csv`
  and glossary demo data. The matching test fixtures are flipped back to `DummyPayment01` in `test_files`.)
- `import_entities_to_remove` - Data import entity names
- `import_yaml_files` - YAML import file paths
- `docker_commands_to_remove` - Docker command names
- `docker_config_files` - Docker configuration file paths
- `docker_config_substitutions` - Literal replacements in the docker configuration files
- `deploy_files` - Deploy file paths
- `directories_to_remove` - Directory paths with display names
- `files_to_remove` - File paths with display names

---
