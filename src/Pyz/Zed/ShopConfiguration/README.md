# Shop Configuration Feature

## Overview

The Shop Configuration feature provides a comprehensive system for managing configuration options across Spryker modules. It discovers configuration files automatically, provides a Backoffice interface for managing values, supports store and locale-specific overrides, and publishes configurations to Redis for fast access by frontend applications.

## Features

- **Automatic Discovery**: Scans modules for YAML, XML, and JSON configuration files
- **Backoffice Management**: User-friendly interface with accessibility features (WCAG 2.1 AA compliant)
- **Store & Locale Support**: Supports defaults with per-store and per-locale-in-store overrides
- **Secure Storage**: Encrypts sensitive configuration values (API keys, secrets)
- **Redis Publishing**: Publishes effective configurations to Redis for fast frontend access
- **Client Access**: Provides Client layer for Yves and Glue applications to read configurations

## Architecture

### Layers

- **Zed Business Layer**: Core logic for file discovery, normalization, validation, and publishing
- **Zed Persistence Layer**: Database storage for configuration overrides
- **Zed Communication Layer**: Backoffice controllers and UI
- **Client Layer**: Frontend access to published configurations
- **Shared Layer**: Transfer objects, constants, and utilities

### Key Components

1. **File Discovery System**
   - Automatically discovers configuration files in modules
   - Supports YAML, XML, and JSON formats
   - Validates configuration schema

2. **Configuration Resolution**
   - Merges file defaults with database overrides
   - Applies precedence rules (store-locale > store > default)
   - Handles sensitive field encryption

3. **Publishing System**
   - Publishes effective configurations to Redis
   - Uses store and locale-specific keys
   - Supports invalidation and rebuilding

4. **Backoffice Interface**
   - Groups configurations by translatable sections
   - Provides form fields for different data types
   - Includes search and filtering capabilities
   - Accessible design with keyboard navigation

## Configuration File Format

Configuration files should be placed in module directories:
- `src/<Namespace>/<Application>/<Module>/Shared/<Module>/ShopConfiguration/`
- `src/<Namespace>/<Application>/<Module>/Communication/Resources/shop_configuration/`

Example configuration file (`ui.yml`):
```yaml
general:
  label: "General Settings"
  description: "Basic UI configuration options"
  options:
    theme:
      label: "Theme"
      description: "UI theme selection"
      type: "select"
      default_value: "default"
      validation:
        required: true
        options: ["default", "dark", "light"]
    debug_mode:
      label: "Debug Mode"
      description: "Enable debug information display"
      type: "boolean"
      default_value: false
      overridable: true
```

## Database Schema

The system uses a single table `spy_shop_configuration` to store overrides:

```sql
CREATE TABLE spy_shop_configuration (
    id_shop_configuration INTEGER PRIMARY KEY AUTO_INCREMENT,
    scope_store VARCHAR(64),
    scope_locale VARCHAR(10),
    config_key VARCHAR(255) NOT NULL,
    value_json TEXT,
    is_encrypted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_scope_store (scope_store),
    INDEX idx_scope_locale (scope_locale),
    INDEX idx_config_key (config_key),
    UNIQUE KEY unique_config_scope (scope_store, scope_locale, config_key)
);
```

## Usage Examples

### Accessing Configuration in Client Applications

```php
// Get Shop Configuration Client
$shopConfigurationClient = $this->getLocator()->shopConfiguration()->client();

// Get all configurations for current store/locale
$allConfigs = $shopConfigurationClient->getConfiguration();

// Get specific configuration value
$theme = $shopConfigurationClient->get('ui.general.theme', 'default');

// Check if configuration exists
if ($shopConfigurationClient->has('ui.general.debug_mode')) {
    // Configuration is available
}

// Get all configurations for a specific module
$uiConfigs = $shopConfigurationClient->getModuleConfiguration('ui');
```

### Managing Configuration in Backoffice

1. Navigate to **Configuration** → **Shop Configuration**
2. Use search to find specific configuration options
3. Expand sections to view and edit options
4. Save individual values or publish all changes
5. Use "Rebuild from Files" to refresh from source files

### Security Considerations

- Sensitive fields (marked as `sensitive: true`) are automatically encrypted
- Uses AES-256-CBC encryption with random initialization vectors
- Encryption keys should be managed through environment variables
- Database access should be restricted to authorized personnel

### Performance Optimization

- Configurations are published to Redis for fast access
- Client layer uses optimized key patterns for quick lookups
- Supports store and locale-specific caching
- Background processes can rebuild configurations without blocking frontend

## API Reference

### Facade Methods

```php
interface ShopConfigurationFacadeInterface
{
    public function getEffectiveConfiguration(?string $store = null, ?string $locale = null): ShopConfigurationCollectionTransfer;
    public function saveConfiguration(ShopConfigurationSaveRequestTransfer $saveRequest): void;
    public function publishConfiguration(?string $store = null, ?string $locale = null): void;
    public function rebuildFromFiles(): void;
}
```

### Client Methods

```php
interface ShopConfigurationClientInterface
{
    public function getConfiguration(?string $locale = null): array;
    public function get(string $configKey, $default = null, ?string $locale = null);
    public function has(string $configKey, ?string $locale = null): bool;
    public function getModuleConfiguration(string $module, ?string $locale = null): array;
    public function isConfigurationAvailable(string $store, ?string $locale = null): bool;
}
```

## Installation & Setup

1. **Database Setup**: Run `vendor/bin/console propel:diff` and `vendor/bin/console propel:migrate`
2. **Transfer Generation**: Run `vendor/bin/console transfer:generate`
3. **Asset Building**: Build frontend assets if using custom themes
4. **Redis Configuration**: Ensure Redis is properly configured for the Storage module
5. **Initial Publish**: Run `vendor/bin/console shop-configuration:publish` to build initial cache

## Testing

Test configurations are provided in the `ShopUiConfiguration` module:
- Sample UI configuration options
- Different data types (boolean, select, text, number)
- Validation rules and sensitive field examples

## Troubleshooting

### Common Issues

1. **Configuration not appearing**: Check file format and placement in correct directories
2. **Values not saving**: Verify database permissions and schema is up to date
3. **Frontend not reflecting changes**: Ensure configurations are published to Redis
4. **Encryption errors**: Check encryption key configuration and OpenSSL availability

### Debug Commands

```bash
# Rebuild all configurations from files
vendor/bin/console shop-configuration:rebuild

# Publish configurations to Redis
vendor/bin/console shop-configuration:publish

# Validate configuration files
vendor/bin/console shop-configuration:validate
```

## Security & Compliance

- **PCI DSS**: Encrypts sensitive payment-related configurations
- **GDPR**: Supports data protection for user-related settings
- **Accessibility**: WCAG 2.1 AA compliant Backoffice interface
- **Authentication**: Integrates with Spryker ACL system
- **Audit Trail**: Logs configuration changes with timestamps and user information

## Extension Points

- **Custom Parsers**: Add support for additional file formats
- **Validation Rules**: Implement custom validation logic
- **Encryption Methods**: Use alternative encryption mechanisms
- **Publishing Targets**: Extend to publish to additional storage systems
- **UI Components**: Add custom form field types and validation

---

For more information, see the Spryker documentation or contact the development team.
