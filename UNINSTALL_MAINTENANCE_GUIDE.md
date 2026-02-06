# Marketplace Uninstall Script - Maintenance Guide

## Overview

The marketplace uninstall system consists of two main components:

1. **uninstall-marketplace-config.json** - Centralized configuration file containing all cleanup recipes
2. **uninstall-marketplace-modules-v2.sh** - Shell script that reads the configuration and executes cleanup operations

This architecture separates configuration from execution logic, making it easier to maintain and extend the uninstall process.

## File Structure

```
project-root/
├── uninstall-marketplace-config.json    # Configuration file
├── uninstall-marketplace-modules-v2.sh  # Execution script
└── UNINSTALL_MAINTENANCE_GUIDE.md       # This guide
```

## Configuration File Structure

The `uninstall-marketplace-config.json` file contains the following top-level sections:

```json
{
  "php_dependency_providers": {},
  "config_files": {},
  "import_entities_to_remove": [],
  "import_yaml_files": [],
  "docker_commands_to_remove": [],
  "docker_config_files": [],
  "deploy_files": [],
  "directories_to_remove": []
}
```

## Supported Operation Types

### 1. PHP Dependency Provider Operations

Location in JSON: `php_dependency_providers`

Each PHP file can have multiple operations:

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
Removes entire methods including docblocks.

```json
{
  "type": "remove_method",
  "method_name": "getExamplePlugins"
}
```

#### Operation: `remove_constant`
Removes constants with their docblocks.

```json
{
  "type": "remove_constant",
  "constant_name": "EXAMPLE_CONSTANT"
}
```

#### Operation: `remove_array_entry`
Removes array entries by key.

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
Removes filesystem configuration blocks.

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

### 3. Simple Array Entries

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

### 4. Directory Removal

Format: `"path:DisplayName"`

```json
"directories_to_remove": [
  "src/Pyz/Zed/Example:Example Module",
  "tests/PyzTest/Zed/Example:Example Tests"
]
```

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

## Best Practices

### 1. Testing Changes

Before running the script:
- Commit your changes to git
- Test on a separate branch
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

1. Add the operation handler in `/tmp/marketplace_cleanup.py` within the script
2. Update the `process_file()` function to handle the new operation type
3. Document the new operation type in this guide

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

## Version Control

When modifying the configuration:

1. Create a feature branch
2. Update the configuration file
3. Test the changes
4. Commit with descriptive message:
   ```bash
   git commit -m "feat: Add removal of NewMarketplaceFeature"
   ```
5. Create a pull request for review

## Running the Script

```bash
# Make script executable (first time only)
chmod +x uninstall-marketplace-modules-v2.sh

# Run the script
./uninstall-marketplace-modules-v2.sh
```

## Safety Checklist

Before running in production:

- [ ] Configuration JSON is valid
- [ ] All file paths are verified
- [ ] Changes are committed to git
- [ ] Testing completed on dev environment
- [ ] Team review completed
- [ ] Backup created (if applicable)

## Support

For questions or issues with the uninstall script:

1. Review this guide
2. Check the script output for specific errors
3. Validate the JSON configuration
4. Review recent changes to the configuration file
5. Consult with the development team

## Changelog

Document significant changes to the configuration:

```
2026-02-02: Initial configuration file created with all marketplace features
```

---

**Last Updated**: February 2, 2026  
**Maintained By**: Development Team
