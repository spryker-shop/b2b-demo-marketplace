#!/bin/bash

# Script to uninstall marketplace-specific packages from B2B Marketplace
# This will remove all marketplace features and related packages

set -e

echo "=========================================="
echo "Uninstalling Marketplace Features"
echo "=========================================="
echo ""

# Marketplace Features
MARKETPLACE_FEATURES=(
    # "spryker-feature/acl"
    "spryker-feature/marketplace-agent-assist"
    "spryker-feature/marketplace-cart"
    "spryker-feature/marketplace-comments"
    "spryker-feature/marketplace-merchant-commission"
    "spryker-feature/marketplace-merchant-contract-requests"
    "spryker-feature/marketplace-merchant-contracts"
    "spryker-feature/marketplace-merchant-custom-prices"
    "spryker-feature/marketplace-merchant-order-threshold"
    "spryker-feature/marketplace-merchant-portal-product-management"
    "spryker-feature/marketplace-merchant-portal-product-offer-management"
    "spryker-feature/marketplace-merchant-portal-product-offer-service-points"
    "spryker-feature/marketplace-merchant-portal-product-offer-shipment"
    "spryker-feature/marketplace-merchantportal-core"
    "spryker-feature/marketplace-order-management"
    "spryker-feature/marketplace-packaging-units"
    "spryker-feature/marketplace-product"
    "spryker-feature/marketplace-product-approval-process"
    "spryker-feature/marketplace-product-options"
    "spryker-feature/marketplace-promotions-discounts"
    "spryker-feature/marketplace-return-management"
    "spryker-feature/marketplace-shopping-lists"
    "spryker-feature/merchant-category"
    "spryker-feature/merchant-opening-hours"
    "spryker-feature/merchant-portal-data-import"


)

# Marketplace Core Modules
MARKETPLACE_CORE_MODULES=(
    "spryker/agent-dashboard-merchant-portal-gui"
    "spryker/agent-security-blocker-merchant-portal-gui"
    "spryker/agent-security-merchant-portal-gui"
    "spryker/availability-merchant-portal-gui"
    "spryker/cart-note-merchant-portal-gui"
    "spryker/category-merchant-commission-connector"
    "spryker/dashboard-merchant-portal-gui"
    "spryker/dummy-marketplace-payment"
    "spryker/merchant-app-merchant-portal-gui"
    "spryker/merchant-categories-rest-api"
    "spryker/merchant-discount-connector"
    "spryker/merchant-opening-hours-rest-api"
    "spryker/merchant-product-offer-shopping-lists-rest-api"
    "spryker/merchant-product-shopping-lists-rest-api"
    "spryker/merchant-products-rest-api"
    "spryker/merchant-profile-merchant-portal-gui"
    "spryker/merchant-sales-returns-rest-api"
    "spryker/merchant-shipments-rest-api"
    "spryker/merchants-rest-api"
    "spryker/multi-factor-auth-merchant-portal"
    "spryker/price-product-merchant-commission-connector"
    "spryker/product-merchant-commission-connector"
    "spryker/product-option-merchant-portal-gui"
    "spryker/sales-merchant-portal-gui"
    "spryker/security-blocker-merchant-portal-gui"
    "spryker/tax-merchant-portal-gui"
)


echo "Step 1: Removing Marketplace Features..."
composer remove --no-update "${MARKETPLACE_FEATURES[@]}"
echo "✓ Marketplace features marked for removal"
echo ""

echo "Step 2: Removing Marketplace Core Modules..."
composer remove --no-update "${MARKETPLACE_CORE_MODULES[@]}"
echo "✓ Marketplace core modules marked for removal"
echo ""


# Create reusable Python cleanup script
create_cleanup_script() {
    cat > /tmp/marketplace_cleanup.py << 'PYTHON_SCRIPT'
import re
import sys
import json

def read_file(file_path):
    """Read file content."""
    with open(file_path, 'r') as f:
        return f.read()

def write_file(file_path, content):
    """Write content to file."""
    with open(file_path, 'w') as f:
        f.write(content)

def remove_use_statement(content, class_name):
    """Remove use statement for a specific class."""
    # Match use statement with the class name
    pattern = rf'use\s+[^;]*\\{re.escape(class_name)};\s*'
    content = re.sub(pattern, '', content)
    return content

def remove_plugin_from_array(content, plugin_class_name):
    """Remove plugin instantiation from array."""
    # Match: new PluginClassName(), or new PluginClassName()
    # Also match: $array[] = new PluginClassName(); (array push syntax)
    
    # First try to match array push syntax: $var[] = new Plugin();
    array_push_pattern = rf'\s*\$\w+\[\]\s*=\s*new\s+{re.escape(plugin_class_name)}\s*\([^)]*\);\s*'
    content = re.sub(array_push_pattern, '', content)
    
    # Then match regular array syntax: new PluginClassName(),
    pattern = rf'\s*new\s+{re.escape(plugin_class_name)}\s*\([^)]*\),?\s*'
    content = re.sub(pattern, '', content)
    
    return content

def remove_event_subscriber(content, subscriber_class_name):
    """Remove event subscriber add() call."""
    # Match: $eventSubscriberCollection->add(new SubscriberClassName());
    pattern = rf'\s*\$\w+->add\(new\s+{re.escape(subscriber_class_name)}\s*\([^)]*\)\);\s*'
    content = re.sub(pattern, '', content)
    return content

def remove_collection_add(content, plugin_class_name):
    """Remove collection->add() call with optional second parameter."""
    # Match: $collection->add(new PluginClassName(), 'optional/string');
    # Also handles multi-line calls with second parameter
    pattern = rf'\s*\$\w+->add\(\s*new\s+{re.escape(plugin_class_name)}\s*\([^)]*\)\s*(?:,\s*[^;]+)?\s*\);\s*'
    content = re.sub(pattern, '', content, flags=re.DOTALL)
    return content

def remove_resource_relationship(content, resource_type, plugin_class_name):
    """Remove addRelationship() call for a specific resource type and plugin."""
    # Match: $resourceRelationshipCollection->addRelationship(
    #     ResourceType::CONSTANT,
    #     new PluginClassName(),
    # );
    pattern = rf'\s*\$\w+->addRelationship\(\s*{re.escape(resource_type)}\s*,\s*new\s+{re.escape(plugin_class_name)}\s*\([^)]*\)\s*,?\s*\);\s*'
    content = re.sub(pattern, '', content, flags=re.DOTALL)
    return content

def remove_method(content, method_name):
    """Remove method with its docblock."""
    # Match method with docblock
    pattern = rf'\s*/\*\*[^/]*\*/\s*(?:public|protected|private)\s+function\s+{re.escape(method_name)}\s*\([^)]*\)[^{{]*\{{(?:[^{{}}]|\{{(?:[^{{}}]|\{{[^{{}}]*\}})*\}})*\}}'
    content = re.sub(pattern, '', content, flags=re.DOTALL)
    return content

def remove_constant(content, constant_name):
    """Remove constant with its docblock."""
    pattern = rf'\s*/\*\*[^/]*\*/\s*(?:public|protected|private)?\s*const\s+{re.escape(constant_name)}\s*=\s*[^;]+;\s*'
    content = re.sub(pattern, '', content, flags=re.DOTALL)
    return content

def remove_array_entry(content, key):
    """Remove array entry by key."""
    pattern = rf"\s*'{re.escape(key)}'\s*=>\s*\[[^\]]+\],\s*"
    content = re.sub(pattern, '', content, flags=re.DOTALL)
    return content

def remove_queue_entry(content, queue_config_key):
    """Remove queue array entry with Config class constant."""
    # Match: ConfigClass::CONSTANT => new Plugin(),
    pattern = rf'\s*{re.escape(queue_config_key)}\s*=>\s*new\s+\w+\([^)]*\),\s*'
    content = re.sub(pattern, '', content)
    return content

def remove_array_constant_entry(content, constant_reference):
    """Remove array entry that is a config constant reference."""
    # Match: SomeConfig::SOME_CONSTANT,
    pattern = rf'\s*{re.escape(constant_reference)}\s*,\s*'
    content = re.sub(pattern, '', content)
    return content

def remove_data_import_console(content, config_constant):
    """Remove DataImportConsole instantiation with specific config constant."""
    # Match: new DataImportConsole(DataImportConsole::DEFAULT_NAME . static::COMMAND_SEPARATOR . ConfigClass::CONSTANT),
    pattern = rf'\s*new\s+DataImportConsole\(DataImportConsole::DEFAULT_NAME\s*\.\s*static::COMMAND_SEPARATOR\s*\.\s*{re.escape(config_constant)}\),?\s*'
    content = re.sub(pattern, '', content, flags=re.MULTILINE)
    return content

def cleanup_content(content):
    """Clean up multiple empty lines and ensure single newline at end."""
    # Clean up multiple consecutive empty lines (more than 2)
    content = re.sub(r'\n{3,}', '\n\n', content)
    # Ensure file ends with single newline
    content = content.rstrip() + '\n'
    return content

def process_file(config):
    """Process file based on configuration."""
    file_path = config['file_path']
    
    content = read_file(file_path)
    
    # Apply all removal operations
    for operation in config.get('operations', []):
        op_type = operation['type']
        
        if op_type == 'remove_use':
            content = remove_use_statement(content, operation['class_name'])
        elif op_type == 'remove_plugin':
            content = remove_plugin_from_array(content, operation['plugin_class'])
        elif op_type == 'remove_event_subscriber':
            content = remove_event_subscriber(content, operation['subscriber_class'])
        elif op_type == 'remove_collection_add':
            content = remove_collection_add(content, operation['plugin_class'])
        elif op_type == 'remove_resource_relationship':
            content = remove_resource_relationship(content, operation['resource_type'], operation['plugin_class'])
        elif op_type == 'remove_method':
            content = remove_method(content, operation['method_name'])
        elif op_type == 'remove_constant':
            content = remove_constant(content, operation['constant_name'])
        elif op_type == 'remove_array_entry':
            content = remove_array_entry(content, operation['key'])
        elif op_type == 'remove_queue_entry':
            content = remove_queue_entry(content, operation['queue_config_key'])
        elif op_type == 'remove_array_constant_entry':
            content = remove_array_constant_entry(content, operation['constant_reference'])
        elif op_type == 'remove_data_import_console':
            content = remove_data_import_console(content, operation['config_constant'])
    
    # Cleanup
    content = cleanup_content(content)
    
    # Write back
    write_file(file_path, content)
    
    # Print success messages
    for message in config.get('success_messages', []):
        print(message)

if __name__ == '__main__':
    config_json = sys.argv[1]
    config = json.loads(config_json)
    process_file(config)
PYTHON_SCRIPT
}

# Function to clean PHP file
clean_php_file() {
    local file_path=$1
    local config_json=$2
    local description=$3
    
    if [ -f "$file_path" ]; then
        # Run Python cleanup
        python3 /tmp/marketplace_cleanup.py "$config_json"
    else
        echo "⚠ File not found at $file_path"
    fi
}

# Function to remove directory and its contents
remove_directory() {
    local dir_path=$1
    local dir_name=$2
    
    if [ -d "$dir_path" ]; then
        rm -rf "$dir_path"
        echo "✓ Removed $dir_name directory"
    else
        echo "⚠ $dir_name directory not found at $dir_path"
    fi
}

# Create the Python cleanup script
create_cleanup_script

# Define all directories to remove
DIRECTORIES_TO_REMOVE=(
    "src/Pyz/Zed/AclEntity:AclEntity"
    "src/Pyz/Zed/AclMerchantAgent:AclMerchantAgent"
    "src/Pyz/Zed/AgentDashboardMerchantPortalGui:AgentDashboardMerchantPortalGui"
    "src/Pyz/Zed/AgentSecurityMerchantPortalGui:AgentSecurityMerchantPortalGui"
    "src/Pyz/Zed/DashboardMerchantPortalGui:DashboardMerchantPortalGui"
    "src/Pyz/Zed/DataImportMerchantPortalGui:DataImportMerchantPortalGui"
    "src/Pyz/Zed/MerchantCommission:MerchantCommission"
    "src/Pyz/Zed/MerchantCategory:MerchantCategory"
    "src/Pyz/Zed/MerchantCommissionGui:MerchantCommissionGui"
    "src/Pyz/Zed/MerchantGui:MerchantGui"
    "src/Pyz/Zed/MerchantOms:MerchantOms"
    "src/Pyz/Zed/MerchantOpeningHours:MerchantOpeningHours"
    "src/Pyz/Zed/MerchantOpeningHoursStorage:MerchantOpeningHoursStorage"
    "src/Pyz/Zed/MerchantPortalApplication:MerchantPortalApplication"
    "src/Pyz/Zed/MerchantProduct:MerchantProduct"
    "src/Pyz/Zed/MerchantProductDataImport:MerchantProductDataImport"
    "src/Pyz/Zed/MerchantProfileMerchantPortalGui:MerchantProfileMerchantPortalGui"
    "src/Pyz/Zed/MerchantRelationshipMerchantPortalGui:MerchantRelationshipMerchantPortalGui"
    "src/Pyz/Zed/MerchantSalesOrder:MerchantSalesOrder"
    "src/Pyz/Zed/MerchantSalesOrderMerchantUserGui:MerchantSalesOrderMerchantUserGui"
    "src/Pyz/Zed/MerchantUser:MerchantUser"
    "src/Pyz/Zed/MultiFactorAuthMerchantPortal:MultiFactorAuthMerchantPortal"
    "src/Pyz/Zed/ProductMerchantPortalGui:ProductMerchantPortalGui"
    "src/Pyz/Zed/ProductOfferMerchantPortalGui:ProductOfferMerchantPortalGui"
    "src/Pyz/Zed/SalesMerchantCommission:SalesMerchantCommission"
    "src/Pyz/Zed/SalesMerchantPortalGui:SalesMerchantPortalGui"
    "src/Pyz/Zed/SalesPaymentMerchant:SalesPaymentMerchant"
    "src/Pyz/Zed/SalesPaymentMerchantSalesMerchantCommission:SalesPaymentMerchantSalesMerchantCommission"
    "src/Pyz/Zed/SecurityMerchantPortalGui:SecurityMerchantPortalGui"
    "src/Pyz/Zed/UserMerchantPortalGui:UserMerchantPortalGui"
    "src/Pyz/Glue/MerchantsRestApi:MerchantsRestApi"
    "src/Pyz/Yves/MerchantSalesReturnWidget:MerchantSalesReturnWidget"
)

echo "Step 4: Removing marketplace-specific code from AclConfig..."
ACLCONFIG_FILE="src/Pyz/Zed/Acl/AclConfig.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Acl/AclConfig.php",
    "operations": [
        {"type": "remove_constant", "constant_name": "RULE_TYPE_DENY"},
        {"type": "remove_method", "method_name": "addMerchantPortalInstallerRules"},
        {"type": "remove_method", "method_name": "getInstallerRules"},
        {"type": "remove_array_entry", "key": "agent-merchant@spryker.com"}
    ],
    "success_messages": [
        "✓ AclConfig cleaned from marketplace-specific code",
        "✓ Removed agent-merchant@spryker.com user"
    ]
}'
clean_php_file "$ACLCONFIG_FILE" "$CONFIG_JSON" "AclConfig"
echo ""

echo "Step 4.5: Removing marketplace-specific plugins from AclDependencyProvider..."
ACL_DEP_FILE="src/Pyz/Zed/Acl/AclDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Acl/AclDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantAgentAclAccessCheckerStrategyPlugin"},
        {"type": "remove_use", "class_name": "ProductViewerForOfferCreationAclInstallerPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantAgentAclAccessCheckerStrategyPlugin"},
        {"type": "remove_plugin", "plugin_class": "ProductViewerForOfferCreationAclInstallerPlugin"}
    ],
    "success_messages": [
        "✓ AclDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantAgentAclAccessCheckerStrategyPlugin",
        "✓ Removed ProductViewerForOfferCreationAclInstallerPlugin"
    ]
}'
clean_php_file "$ACL_DEP_FILE" "$CONFIG_JSON" "AclDependencyProvider"
echo ""

echo "Step 5: Removing marketplace-specific code from AclEntityDependencyProvider..."
ACL_ENTITY_DEP_FILE="src/Pyz/Zed/AclEntity/AclEntityDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/AclEntity/AclEntityDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantPortalConfigurationAclEntityMetadataConfigExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantPortalConfigurationAclEntityMetadataConfigExpanderPlugin"}
    ],
    "success_messages": [
        "✓ AclEntityDependencyProvider cleaned from marketplace-specific code",
        "✓ Removed MerchantPortalConfigurationAclEntityMetadataConfigExpanderPlugin"
    ]
}'
clean_php_file "$ACL_ENTITY_DEP_FILE" "$CONFIG_JSON" "AclEntityDependencyProvider"
echo ""

echo "Step 6: Removing marketplace-specific plugins from AvailabilityGuiDependencyProvider..."
AVAILABILITY_GUI_DEP_FILE="src/Pyz/Zed/AvailabilityGui/AvailabilityGuiDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/AvailabilityGui/AvailabilityGuiDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantAvailabilityListActionViewDataExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductAvailabilityAbstractTableQueryCriteriaExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductAvailabilityViewActionViewDataExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantAvailabilityListActionViewDataExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductAvailabilityAbstractTableQueryCriteriaExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductAvailabilityViewActionViewDataExpanderPlugin"}
    ],
    "success_messages": [
        "✓ AvailabilityGuiDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantProductAvailabilityViewActionViewDataExpanderPlugin",
        "✓ Removed MerchantProductAvailabilityAbstractTableQueryCriteriaExpanderPlugin",
        "✓ Removed MerchantAvailabilityListActionViewDataExpanderPlugin"
    ]
}'
clean_php_file "$AVAILABILITY_GUI_DEP_FILE" "$CONFIG_JSON" "AvailabilityGuiDependencyProvider"
echo ""

echo "Step 7: Removing marketplace-specific plugins from CalculationDependencyProvider..."
CALCULATION_DEP_FILE="src/Pyz/Zed/Calculation/CalculationDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Calculation/CalculationDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantCommissionCalculatorPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantCommissionCalculatorPlugin"}
    ],
    "success_messages": [
        "✓ CalculationDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantCommissionCalculatorPlugin"
    ]
}'
clean_php_file "$CALCULATION_DEP_FILE" "$CONFIG_JSON" "CalculationDependencyProvider"
echo ""

echo "Step 8: Removing marketplace-specific plugins from CartDependencyProvider..."
CART_DEP_FILE="src/Pyz/Zed/Cart/CartDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Cart/CartDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "ProductApprovalCartPreCheckPlugin"},
        {"type": "remove_use", "class_name": "OrderAmendmentProductApprovalCartPreCheckPlugin"},
        {"type": "remove_use", "class_name": "SanitizeMerchantCommissionPreReloadPlugin"},
        {"type": "remove_use", "class_name": "ProductApprovalPreReloadItemsPlugin"},
        {"type": "remove_use", "class_name": "OrderAmendmentProductApprovalPreReloadItemsPlugin"},
        {"type": "remove_use", "class_name": "MerchantShipmentItemExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantCartPreCheckPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductCartPreCheckPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductOptionCartPreCheckPlugin"},
        {"type": "remove_plugin", "plugin_class": "ProductApprovalCartPreCheckPlugin"},
        {"type": "remove_plugin", "plugin_class": "OrderAmendmentProductApprovalCartPreCheckPlugin"},
        {"type": "remove_plugin", "plugin_class": "SanitizeMerchantCommissionPreReloadPlugin"},
        {"type": "remove_plugin", "plugin_class": "ProductApprovalPreReloadItemsPlugin"},
        {"type": "remove_plugin", "plugin_class": "OrderAmendmentProductApprovalPreReloadItemsPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantShipmentItemExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantCartPreCheckPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductCartPreCheckPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductOptionCartPreCheckPlugin"}
    ],
    "success_messages": [
        "✓ CartDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed ProductApprovalCartPreCheckPlugin",
        "✓ Removed OrderAmendmentProductApprovalCartPreCheckPlugin",
        "✓ Removed SanitizeMerchantCommissionPreReloadPlugin",
        "✓ Removed ProductApprovalPreReloadItemsPlugin",
        "✓ Removed OrderAmendmentProductApprovalPreReloadItemsPlugin",
        "✓ Removed MerchantShipmentItemExpanderPlugin",
        "✓ Removed MerchantCartPreCheckPlugin",
        "✓ Removed MerchantProductCartPreCheckPlugin",
        "✓ Removed MerchantProductOptionCartPreCheckPlugin"
    ]
}'
clean_php_file "$CART_DEP_FILE" "$CONFIG_JSON" "CartDependencyProvider"
echo ""

echo "Step 9: Removing marketplace-specific plugins from CartReorderDependencyProvider..."
CART_REORDER_DEP_FILE="src/Pyz/Zed/CartReorder/CartReorderDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/CartReorder/CartReorderDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantProductCartReorderItemHydratorPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductOfferCartReorderItemHydratorPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductCartReorderItemHydratorPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductOfferCartReorderItemHydratorPlugin"}
    ],
    "success_messages": [
        "✓ CartReorderDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantProductCartReorderItemHydratorPlugin",
        "✓ Removed MerchantProductOfferCartReorderItemHydratorPlugin"
    ]
}'
clean_php_file "$CART_REORDER_DEP_FILE" "$CONFIG_JSON" "CartReorderDependencyProvider"
echo ""

echo "Step 10: Removing marketplace-specific plugins from CategoryDependencyProvider..."
CATEGORY_DEP_FILE="src/Pyz/Zed/Category/CategoryDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Category/CategoryDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "RemoveMerchantCategoryRelationPlugin"},
        {"type": "remove_plugin", "plugin_class": "RemoveMerchantCategoryRelationPlugin"}
    ],
    "success_messages": [
        "✓ CategoryDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed RemoveMerchantCategoryRelationPlugin"
    ]
}'
clean_php_file "$CATEGORY_DEP_FILE" "$CONFIG_JSON" "CategoryDependencyProvider"
echo ""

echo "Step 11: Removing marketplace-specific plugins from CheckoutDependencyProvider..."
CHECKOUT_DEP_FILE="src/Pyz/Zed/Checkout/CheckoutDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Checkout/CheckoutDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantProductOptionCheckoutPreConditionPlugin"},
        {"type": "remove_use", "class_name": "ProductApprovalCheckoutPreConditionPlugin"},
        {"type": "remove_use", "class_name": "MerchantCheckoutPreConditionPlugin"},
        {"type": "remove_use", "class_name": "OrderAmendmentProductApprovalCheckoutPreConditionPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductOptionCheckoutPreConditionPlugin"},
        {"type": "remove_plugin", "plugin_class": "ProductApprovalCheckoutPreConditionPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantCheckoutPreConditionPlugin"},
        {"type": "remove_plugin", "plugin_class": "OrderAmendmentProductApprovalCheckoutPreConditionPlugin"}
    ],
    "success_messages": [
        "✓ CheckoutDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantProductOptionCheckoutPreConditionPlugin",
        "✓ Removed ProductApprovalCheckoutPreConditionPlugin",
        "✓ Removed MerchantCheckoutPreConditionPlugin",
        "✓ Removed OrderAmendmentProductApprovalCheckoutPreConditionPlugin"
    ]
}'
clean_php_file "$CHECKOUT_DEP_FILE" "$CONFIG_JSON" "CheckoutDependencyProvider"
echo ""

echo "Step 12: Removing marketplace-specific plugins from DataImportDependencyProvider..."
DATA_IMPORT_DEP_FILE="src/Pyz/Zed/DataImport/DataImportDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/DataImport/DataImportDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantProductDataImportPlugin"},
        {"type": "remove_use", "class_name": "ProductOfferShoppingListItemDataImportPlugin"},
        {"type": "remove_use", "class_name": "MerchantOmsProcessDataImportPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductOptionGroupDataImportPlugin"},
        {"type": "remove_use", "class_name": "MerchantOpeningHoursDateScheduleDataImportPlugin"},
        {"type": "remove_use", "class_name": "MerchantOpeningHoursWeekdayScheduleDataImportPlugin"},
        {"type": "remove_use", "class_name": "MerchantCategoryDataImportPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductApprovalStatusDefaultDataImportPlugin"},
        {"type": "remove_use", "class_name": "MerchantCommissionGroupDataImportPlugin"},
        {"type": "remove_use", "class_name": "MerchantCommissionDataImportPlugin"},
        {"type": "remove_use", "class_name": "MerchantCommissionAmountDataImportPlugin"},
        {"type": "remove_use", "class_name": "MerchantCommissionStoreDataImportPlugin"},
        {"type": "remove_use", "class_name": "MerchantCommissionMerchantDataImportPlugin"},
        {"type": "remove_use", "class_name": "MerchantCombinedProductDataImportPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductDataImportPlugin"},
        {"type": "remove_plugin", "plugin_class": "ProductOfferShoppingListItemDataImportPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantOmsProcessDataImportPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductOptionGroupDataImportPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantOpeningHoursDateScheduleDataImportPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantOpeningHoursWeekdayScheduleDataImportPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantCategoryDataImportPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductApprovalStatusDefaultDataImportPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantCommissionGroupDataImportPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantCommissionDataImportPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantCommissionAmountDataImportPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantCommissionStoreDataImportPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantCommissionMerchantDataImportPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantCombinedProductDataImportPlugin"}
    ],
    "success_messages": [
        "✓ DataImportDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantProductDataImportPlugin",
        "✓ Removed ProductOfferShoppingListItemDataImportPlugin",
        "✓ Removed MerchantOmsProcessDataImportPlugin",
        "✓ Removed MerchantProductOptionGroupDataImportPlugin",
        "✓ Removed MerchantOpeningHoursDateScheduleDataImportPlugin",
        "✓ Removed MerchantOpeningHoursWeekdayScheduleDataImportPlugin",
        "✓ Removed MerchantCategoryDataImportPlugin",
        "✓ Removed MerchantProductApprovalStatusDefaultDataImportPlugin",
        "✓ Removed MerchantCommissionGroupDataImportPlugin",
        "✓ Removed MerchantCommissionDataImportPlugin",
        "✓ Removed MerchantCommissionAmountDataImportPlugin",
        "✓ Removed MerchantCommissionStoreDataImportPlugin",
        "✓ Removed MerchantCommissionMerchantDataImportPlugin",
        "✓ Removed MerchantCombinedProductDataImportPlugin"
    ]
}'
clean_php_file "$DATA_IMPORT_DEP_FILE" "$CONFIG_JSON" "DataImportDependencyProvider"
echo ""

echo "Step 14: Removing marketplace-specific plugins from ConsoleDependencyProvider..."
CONSOLE_DEP_FILE="src/Pyz/Zed/Console/ConsoleDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Console/ConsoleDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "AclEntitySynchronizeConsole"},
        {"type": "remove_use", "class_name": "AclEntityMetadataConfigValidateConsole"},
        {"type": "remove_use", "class_name": "DataImportMerchantImportConsole"},
        {"type": "remove_use", "class_name": "TriggerEventFromCsvFileConsole"},
        {"type": "remove_use", "class_name": "MerchantProductApprovalDataImportConfig"},
        {"type": "remove_use", "class_name": "ProductOfferShoppingListDataImportConfig"},
        {"type": "remove_use", "class_name": "MerchantCommissionDataImportConfig"},
        {"type": "remove_use", "class_name": "ProductOfferServicePointDataImportConfig"},
        {"type": "remove_use", "class_name": "ProductOfferShipmentTypeDataImportConfig"},
        {"type": "remove_plugin", "plugin_class": "AclEntitySynchronizeConsole"},
        {"type": "remove_plugin", "plugin_class": "AclEntityMetadataConfigValidateConsole"},
        {"type": "remove_plugin", "plugin_class": "DataImportMerchantImportConsole"},
        {"type": "remove_plugin", "plugin_class": "TriggerEventFromCsvFileConsole"},
        {"type": "remove_data_import_console", "config_constant": "MerchantProductApprovalDataImportConfig::IMPORT_TYPE_MERCHANT_PRODUCT_APPROVAL_STATUS_DEFAULT"},
        {"type": "remove_data_import_console", "config_constant": "ProductOfferShoppingListDataImportConfig::IMPORT_TYPE_PRODUCT_OFFER_SHOPPING_LIST_ITEM"},
        {"type": "remove_data_import_console", "config_constant": "MerchantCommissionDataImportConfig::IMPORT_TYPE_MERCHANT_COMMISSION_GROUP"},
        {"type": "remove_data_import_console", "config_constant": "MerchantCommissionDataImportConfig::IMPORT_TYPE_MERCHANT_COMMISSION"},
        {"type": "remove_data_import_console", "config_constant": "MerchantCommissionDataImportConfig::IMPORT_TYPE_MERCHANT_COMMISSION_AMOUNT"},
        {"type": "remove_data_import_console", "config_constant": "MerchantCommissionDataImportConfig::IMPORT_TYPE_MERCHANT_COMMISSION_STORE"},
        {"type": "remove_data_import_console", "config_constant": "MerchantCommissionDataImportConfig::IMPORT_TYPE_MERCHANT_COMMISSION_MERCHANT"},
        {"type": "remove_data_import_console", "config_constant": "ProductOfferServicePointDataImportConfig::IMPORT_TYPE_PRODUCT_OFFER_SERVICE"},
        {"type": "remove_data_import_console", "config_constant": "ProductOfferShipmentTypeDataImportConfig::IMPORT_TYPE_PRODUCT_OFFER_SHIPMENT_TYPE"}
    ],
    "success_messages": [
        "✓ ConsoleDependencyProvider cleaned from marketplace-specific console commands",
        "✓ Removed AclEntitySynchronizeConsole",
        "✓ Removed AclEntityMetadataConfigValidateConsole",
        "✓ Removed DataImportMerchantImportConsole",
        "✓ Removed TriggerEventFromCsvFileConsole",
        "✓ Removed merchant product approval data import console",
        "✓ Removed product offer shopping list data import console",
        "✓ Removed merchant commission data import consoles",
        "✓ Removed product offer service point data import console",
        "✓ Removed product offer shipment type data import console"
    ]
}'
clean_php_file "$CONSOLE_DEP_FILE" "$CONFIG_JSON" "ConsoleDependencyProvider"
echo ""

echo "Step 15: Removing marketplace-specific plugins from DataExportDependencyProvider..."
DATA_EXPORT_DEP_FILE="src/Pyz/Zed/DataExport/DataExportDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/DataExport/DataExportDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantOrderDataEntityExporterPlugin"},
        {"type": "remove_use", "class_name": "MerchantOrderExpenseDataEntityExporterPlugin"},
        {"type": "remove_use", "class_name": "MerchantOrderItemDataEntityExporterPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantOrderDataEntityExporterPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantOrderItemDataEntityExporterPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantOrderExpenseDataEntityExporterPlugin"}
    ],
    "success_messages": [
        "✓ DataExportDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantOrderDataEntityExporterPlugin",
        "✓ Removed MerchantOrderItemDataEntityExporterPlugin",
        "✓ Removed MerchantOrderExpenseDataEntityExporterPlugin"
    ]
}'
clean_php_file "$DATA_EXPORT_DEP_FILE" "$CONFIG_JSON" "DataExportDependencyProvider"
echo ""

echo "Step 16: Removing marketplace-specific plugins from DiscountDependencyProvider..."
DISCOUNT_DEP_FILE="src/Pyz/Zed/Discount/DiscountDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Discount/DiscountDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantReferenceDecisionRulePlugin"},
        {"type": "remove_use", "class_name": "MerchantReferenceDiscountableItemCollectorPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantReferenceDecisionRulePlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantReferenceDiscountableItemCollectorPlugin"}
    ],
    "success_messages": [
        "✓ DiscountDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantReferenceDecisionRulePlugin",
        "✓ Removed MerchantReferenceDiscountableItemCollectorPlugin"
    ]
}'
clean_php_file "$DISCOUNT_DEP_FILE" "$CONFIG_JSON" "DiscountDependencyProvider"
echo ""

echo "Step 17: Removing marketplace-specific subscribers from EventDependencyProvider..."
EVENT_DEP_FILE="src/Pyz/Zed/Event/EventDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Event/EventDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantProductOfferSearchEventSubscriber"},
        {"type": "remove_event_subscriber", "subscriber_class": "MerchantProductOfferSearchEventSubscriber"}
    ],
    "success_messages": [
        "✓ EventDependencyProvider cleaned from marketplace-specific subscribers",
        "✓ Removed MerchantProductOfferSearchEventSubscriber"
    ]
}'
clean_php_file "$EVENT_DEP_FILE" "$CONFIG_JSON" "EventDependencyProvider"
echo ""

echo "Step 19: Removing marketplace-specific plugins from OauthUserConnectorDependencyProvider..."
OAUTH_USER_CONNECTOR_DEP_FILE="src/Pyz/Zed/OauthUserConnector/OauthUserConnectorDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/OauthUserConnector/OauthUserConnectorDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantUserTypeOauthScopeProviderPlugin"},
        {"type": "remove_use", "class_name": "MerchantUserTypeOauthScopeAuthorizationCheckerPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantUserTypeOauthScopeProviderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantUserTypeOauthScopeAuthorizationCheckerPlugin"}
    ],
    "success_messages": [
        "✓ OauthUserConnectorDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantUserTypeOauthScopeProviderPlugin",
        "✓ Removed MerchantUserTypeOauthScopeAuthorizationCheckerPlugin"
    ]
}'
clean_php_file "$OAUTH_USER_CONNECTOR_DEP_FILE" "$CONFIG_JSON" "OauthUserConnectorDependencyProvider"
echo ""

echo "Step 20: Removing marketplace-specific plugins from OmsDependencyProvider..."
OMS_DEP_FILE="src/Pyz/Zed/Oms/OmsDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Oms/OmsDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "IsOrderPaidConditionPlugin"},
        {"type": "remove_use", "class_name": "CreateMerchantOrdersCommandPlugin"},
        {"type": "remove_use", "class_name": "CloseMerchantOrderItemCommandPlugin"},
        {"type": "remove_use", "class_name": "ReturnMerchantOrderItemCommandPlugin"},
        {"type": "remove_use", "class_name": "IsMerchantPaidOutConditionPlugin"},
        {"type": "remove_use", "class_name": "IsMerchantPayoutReversedConditionPlugin"},
        {"type": "remove_use", "class_name": "SalesMerchantCommissionCalculationCommandByOrderPlugin"},
        {"type": "remove_use", "class_name": "MerchantPayoutCommandByOrderPlugin"},
        {"type": "remove_use", "class_name": "MerchantPayoutReverseCommandByOrderPlugin"},
        {"type": "remove_collection_add", "plugin_class": "IsOrderPaidConditionPlugin"},
        {"type": "remove_collection_add", "plugin_class": "IsMerchantPaidOutConditionPlugin"},
        {"type": "remove_collection_add", "plugin_class": "IsMerchantPayoutReversedConditionPlugin"},
        {"type": "remove_collection_add", "plugin_class": "CreateMerchantOrdersCommandPlugin"},
        {"type": "remove_collection_add", "plugin_class": "CloseMerchantOrderItemCommandPlugin"},
        {"type": "remove_collection_add", "plugin_class": "ReturnMerchantOrderItemCommandPlugin"},
        {"type": "remove_collection_add", "plugin_class": "SalesMerchantCommissionCalculationCommandByOrderPlugin"},
        {"type": "remove_collection_add", "plugin_class": "MerchantPayoutCommandByOrderPlugin"},
        {"type": "remove_collection_add", "plugin_class": "MerchantPayoutReverseCommandByOrderPlugin"}
    ],
    "success_messages": [
        "✓ OmsDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed IsOrderPaidConditionPlugin",
        "✓ Removed IsMerchantPaidOutConditionPlugin",
        "✓ Removed IsMerchantPayoutReversedConditionPlugin",
        "✓ Removed CreateMerchantOrdersCommandPlugin",
        "✓ Removed CloseMerchantOrderItemCommandPlugin",
        "✓ Removed ReturnMerchantOrderItemCommandPlugin",
        "✓ Removed SalesMerchantCommissionCalculationCommandByOrderPlugin",
        "✓ Removed MerchantPayoutCommandByOrderPlugin",
        "✓ Removed MerchantPayoutReverseCommandByOrderPlugin"
    ]
}'
clean_php_file "$OMS_DEP_FILE" "$CONFIG_JSON" "OmsDependencyProvider"
echo ""

echo "Step 21: Removing marketplace-specific plugins from PaymentDependencyProvider..."
PAYMENT_DEP_FILE="src/Pyz/Zed/Payment/PaymentDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Payment/PaymentDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantProductItemPaymentMethodFilterPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductItemPaymentMethodFilterPlugin"}
    ],
    "success_messages": [
        "✓ PaymentDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantProductItemPaymentMethodFilterPlugin"
    ]
}'
clean_php_file "$PAYMENT_DEP_FILE" "$CONFIG_JSON" "PaymentDependencyProvider"
echo ""


echo "Step 23: Removing marketplace-specific plugins from ProductDependencyProvider..."
PRODUCT_DEP_FILE="src/Pyz/Zed/Product/ProductDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Product/ProductDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantProductProductAbstractPostCreatePlugin"},
        {"type": "remove_use", "class_name": "MerchantProductApprovalProductAbstractPreCreatePlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductProductAbstractPostCreatePlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductApprovalProductAbstractPreCreatePlugin"}
    ],
    "success_messages": [
        "✓ ProductDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantProductProductAbstractPostCreatePlugin",
        "✓ Removed MerchantProductApprovalProductAbstractPreCreatePlugin",
        "✓ Removed MerchantProductOfferProductConcreteExpanderPlugin"
    ]
}'
clean_php_file "$PRODUCT_DEP_FILE" "$CONFIG_JSON" "ProductDependencyProvider"
echo ""

echo "Step 26: Removing marketplace-specific plugins from ProductManagementDependencyProvider..."
PRODUCT_MANAGEMENT_DEP_FILE="src/Pyz/Zed/ProductManagement/ProductManagementDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/ProductManagement/ProductManagementDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantProductAbstractListActionViewDataExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductProductAbstractEditViewExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductProductAbstractViewActionViewDataExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductProductTableQueryCriteriaExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductAbstractListActionViewDataExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductProductAbstractEditViewExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductProductAbstractViewActionViewDataExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductProductTableQueryCriteriaExpanderPlugin"}
    ],
    "success_messages": [
        "✓ ProductManagementDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantProductProductAbstractEditViewExpanderPlugin",
        "✓ Removed MerchantProductProductAbstractViewActionViewDataExpanderPlugin",
        "✓ Removed MerchantProductProductTableQueryCriteriaExpanderPlugin",
        "✓ Removed MerchantProductAbstractListActionViewDataExpanderPlugin"
    ]
}'
clean_php_file "$PRODUCT_MANAGEMENT_DEP_FILE" "$CONFIG_JSON" "ProductManagementDependencyProvider"
echo ""

echo "Step 27: Removing marketplace-specific plugins from ProductOptionDependencyProvider..."
PRODUCT_OPTION_DEP_FILE="src/Pyz/Zed/ProductOption/ProductOptionDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/ProductOption/ProductOptionDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantProductOptionListActionViewDataExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductOptionGroupExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductOptionListTableQueryCriteriaExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductOptionListActionViewDataExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductOptionListTableQueryCriteriaExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductOptionGroupExpanderPlugin"}
    ],
    "success_messages": [
        "✓ ProductOptionDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantProductOptionListActionViewDataExpanderPlugin",
        "✓ Removed MerchantProductOptionListTableQueryCriteriaExpanderPlugin",
        "✓ Removed MerchantProductOptionGroupExpanderPlugin"
    ]
}'
clean_php_file "$PRODUCT_OPTION_DEP_FILE" "$CONFIG_JSON" "ProductOptionDependencyProvider"
echo ""

echo "Step 28: Removing marketplace-specific plugins from ProductOptionStorageDependencyProvider..."
PRODUCT_OPTION_STORAGE_DEP_FILE="src/Pyz/Zed/ProductOptionStorage/ProductOptionStorageDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/ProductOptionStorage/ProductOptionStorageDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantProductOptionCollectionFilterPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductOptionCollectionFilterPlugin"}
    ],
    "success_messages": [
        "✓ ProductOptionStorageDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantProductOptionCollectionFilterPlugin"
    ]
}'
clean_php_file "$PRODUCT_OPTION_STORAGE_DEP_FILE" "$CONFIG_JSON" "ProductOptionStorageDependencyProvider"
echo ""

echo "Step 29: Removing marketplace-specific plugins from ProductStorageDependencyProvider..."
PRODUCT_STORAGE_DEP_FILE="src/Pyz/Zed/ProductStorage/ProductStorageDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/ProductStorage/ProductStorageDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantProductAbstractStorageExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductConcreteStorageCollectionExpanderPlugin"},
        {"type": "remove_use", "class_name": "ProductApprovalProductAbstractStorageCollectionFilterPlugin"},
        {"type": "remove_use", "class_name": "ProductApprovalProductConcreteStorageCollectionFilterPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductAbstractStorageExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductConcreteStorageCollectionExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "ProductApprovalProductAbstractStorageCollectionFilterPlugin"},
        {"type": "remove_plugin", "plugin_class": "ProductApprovalProductConcreteStorageCollectionFilterPlugin"}
    ],
    "success_messages": [
        "✓ ProductStorageDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantProductAbstractStorageExpanderPlugin",
        "✓ Removed MerchantProductConcreteStorageCollectionExpanderPlugin",
        "✓ Removed ProductApprovalProductAbstractStorageCollectionFilterPlugin",
        "✓ Removed ProductApprovalProductConcreteStorageCollectionFilterPlugin"
    ]
}'
clean_php_file "$PRODUCT_STORAGE_DEP_FILE" "$CONFIG_JSON" "ProductStorageDependencyProvider"
echo ""

echo "Step 30: Removing marketplace-specific plugins from PublisherDependencyProvider..."
PUBLISHER_DEP_FILE="src/Pyz/Zed/Publisher/PublisherDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Publisher/PublisherDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "CategoryWritePublisherPlugin"},
        {"type": "remove_use", "class_name": "MerchantOpeningHoursDateScheduleWritePublisherPlugin"},
        {"type": "remove_use", "class_name": "MerchantOpeningHoursWeekdayScheduleWritePublisherPlugin"},
        {"type": "remove_use", "class_name": "MerchantOpeningHoursWritePublisherPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductOptionGroupWritePublisherPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductSearchWritePublisherPlugin"},
        {"type": "remove_use", "class_name": "MerchantUpdatePublisherPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductWritePublisherPlugin"},
        {"type": "remove_use", "class_name": "MerchantCategoryWritePublisherPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductOptionGroupPublisherTriggerPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductSearchPublisherTriggerPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductPublisherTriggerPlugin"},
        {"type": "remove_use", "class_name": "MerchantCategoryStoragePublisherPlugin"},
        {"type": "remove_plugin", "plugin_class": "CategoryWritePublisherPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantOpeningHoursWritePublisherPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantOpeningHoursWeekdayScheduleWritePublisherPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantOpeningHoursDateScheduleWritePublisherPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductWritePublisherPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantUpdatePublisherPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantMerchantProductSearchWritePublisherPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductSearchWritePublisherPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantCategoryWritePublisherPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductOptionGroupWritePublisherPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductOptionGroupPublisherTriggerPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductSearchPublisherTriggerPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductPublisherTriggerPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantCategoryStoragePublisherPlugin"}
    ],
    "success_messages": [
        "✓ PublisherDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed CategoryWritePublisherPlugin (MerchantCategory)",
        "✓ Removed MerchantOpeningHoursWritePublisherPlugin",
        "✓ Removed MerchantOpeningHoursWeekdayScheduleWritePublisherPlugin",
        "✓ Removed MerchantOpeningHoursDateScheduleWritePublisherPlugin",
        "✓ Removed MerchantProductWritePublisherPlugin",
        "✓ Removed MerchantUpdatePublisherPlugin",
        "✓ Removed MerchantMerchantProductSearchWritePublisherPlugin",
        "✓ Removed MerchantProductSearchWritePublisherPlugin",
        "✓ Removed MerchantCategoryWritePublisherPlugin",
        "✓ Removed MerchantProductOptionGroupWritePublisherPlugin",
        "✓ Removed MerchantProductOptionGroupPublisherTriggerPlugin",
        "✓ Removed MerchantProductSearchPublisherTriggerPlugin",
        "✓ Removed MerchantProductPublisherTriggerPlugin",
        "✓ Removed MerchantCategoryStoragePublisherPlugin"
    ]
}'
clean_php_file "$PUBLISHER_DEP_FILE" "$CONFIG_JSON" "PublisherDependencyProvider"
echo ""

echo "Step 31: Removing marketplace-specific queue entries from QueueDependencyProvider..."
QUEUE_DEP_FILE="src/Pyz/Zed/Queue/QueueDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Queue/QueueDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantOpeningHoursStorageConfig"},
        {"type": "remove_queue_entry", "queue_config_key": "MerchantOpeningHoursStorageConfig::MERCHANT_OPENING_HOURS_SYNC_STORAGE_QUEUE"}
    ],
    "success_messages": [
        "✓ Removed MerchantOpeningHoursStorageConfig::MERCHANT_OPENING_HOURS_SYNC_STORAGE_QUEUE"
    ]
}'
clean_php_file "$QUEUE_DEP_FILE" "$CONFIG_JSON" "QueueDependencyProvider"
echo ""

echo "Step 32: Removing marketplace-specific plugins from QuoteDependencyProvider..."
QUOTE_DEP_FILE="src/Pyz/Zed/Quote/QuoteDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Quote/QuoteDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantShipmentQuoteExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantShipmentQuoteExpanderPlugin"}
    ],
    "success_messages": [
        "✓ QuoteDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantShipmentQuoteExpanderPlugin"
    ]
}'
clean_php_file "$QUOTE_DEP_FILE" "$CONFIG_JSON" "QuoteDependencyProvider"
echo ""

echo "Step 33: Removing marketplace-specific plugins from RefundDependencyProvider..."
REFUND_DEP_FILE="src/Pyz/Zed/Refund/RefundDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Refund/RefundDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantOrderTotalsRefundPostSavePlugin"},
        {"type": "remove_use", "class_name": "MerchantCommissionRefundPostSavePlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantOrderTotalsRefundPostSavePlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantCommissionRefundPostSavePlugin"}
    ],
    "success_messages": [
        "✓ RefundDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantOrderTotalsRefundPostSavePlugin",
        "✓ Removed MerchantCommissionRefundPostSavePlugin"
    ]
}'
clean_php_file "$REFUND_DEP_FILE" "$CONFIG_JSON" "RefundDependencyProvider"
echo ""

echo "Step 34: Removing marketplace-specific plugins from RouterDependencyProvider..."
ROUTER_DEP_FILE="src/Pyz/Zed/Router/RouterDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Router/RouterDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantPortalRouterPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantPortalRouterPlugin"}
    ],
    "success_messages": [
        "✓ RouterDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantPortalRouterPlugin"
    ]
}'
clean_php_file "$ROUTER_DEP_FILE" "$CONFIG_JSON" "RouterDependencyProvider"
echo ""

echo "Step 35: Removing marketplace-specific plugins from RuleEngineDependencyProvider..."
RULE_ENGINE_DEP_FILE="src/Pyz/Zed/RuleEngine/RuleEngineDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/RuleEngine/RuleEngineDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantCommissionItemCollectorRuleSpecificationProviderPlugin"},
        {"type": "remove_use", "class_name": "MerchantCommissionOrderDecisionRuleSpecificationProviderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantCommissionItemCollectorRuleSpecificationProviderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantCommissionOrderDecisionRuleSpecificationProviderPlugin"}
    ],
    "success_messages": [
        "✓ RuleEngineDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantCommissionItemCollectorRuleSpecificationProviderPlugin",
        "✓ Removed MerchantCommissionOrderDecisionRuleSpecificationProviderPlugin"
    ]
}'
clean_php_file "$RULE_ENGINE_DEP_FILE" "$CONFIG_JSON" "RuleEngineDependencyProvider"
echo ""

echo "Step 36: Removing marketplace-specific plugins from SalesDependencyProvider..."
SALES_DEP_FILE="src/Pyz/Zed/Sales/SalesDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Sales/SalesDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantOmsStateOrderItemsTableExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantDataOrderHydratePlugin"},
        {"type": "remove_use", "class_name": "MerchantOrderDataOrderExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantReferenceOrderItemExpanderPreSavePlugin"},
        {"type": "remove_use", "class_name": "MerchantReferencesOrderExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantCommissionOrderPostCancelPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantOrderDataOrderExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantReferencesOrderExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantDataOrderHydratePlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantReferenceOrderItemExpanderPreSavePlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantOmsStateOrderItemsTableExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantCommissionOrderPostCancelPlugin"}
    ],
    "success_messages": [
        "✓ SalesDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantOrderDataOrderExpanderPlugin",
        "✓ Removed MerchantReferencesOrderExpanderPlugin",
        "✓ Removed MerchantDataOrderHydratePlugin",
        "✓ Removed MerchantReferenceOrderItemExpanderPreSavePlugin",
        "✓ Removed MerchantOmsStateOrderItemsTableExpanderPlugin",
        "✓ Removed MerchantCommissionOrderPostCancelPlugin"
    ]
}'
clean_php_file "$SALES_DEP_FILE" "$CONFIG_JSON" "SalesDependencyProvider"
echo ""

echo "Step 37: Removing marketplace-specific plugins from SalesReturnDependencyProvider..."
SALES_RETURN_DEP_FILE="src/Pyz/Zed/SalesReturn/SalesReturnDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/SalesReturn/SalesReturnDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantReturnCreateRequestValidatorPlugin"},
        {"type": "remove_use", "class_name": "MerchantReturnExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantReturnPreCreatePlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantReturnPreCreatePlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantReturnCreateRequestValidatorPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantReturnExpanderPlugin"}
    ],
    "success_messages": [
        "✓ SalesReturnDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantReturnPreCreatePlugin",
        "✓ Removed MerchantReturnCreateRequestValidatorPlugin",
        "✓ Removed MerchantReturnExpanderPlugin"
    ]
}'
clean_php_file "$SALES_RETURN_DEP_FILE" "$CONFIG_JSON" "SalesReturnDependencyProvider"
echo ""

echo "Step 38: Removing marketplace-specific plugins from SalesReturnGuiDependencyProvider..."
SALES_RETURN_GUI_DEP_FILE="src/Pyz/Zed/SalesReturnGui/SalesReturnGuiDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/SalesReturnGui/SalesReturnGuiDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantSalesReturnCreateFormHandlerPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantSalesReturnCreateFormHandlerPlugin"}
    ],
    "success_messages": [
        "✓ SalesReturnGuiDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantSalesReturnCreateFormHandlerPlugin"
    ]
}'
clean_php_file "$SALES_RETURN_GUI_DEP_FILE" "$CONFIG_JSON" "SalesReturnGuiDependencyProvider"
echo ""

echo "Step 39: Removing marketplace-specific plugins from SecurityDependencyProvider..."
SECURITY_DEP_FILE="src/Pyz/Zed/Security/SecurityDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Security/SecurityDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MultiFactorAuthenticationAgentMerchantUserSecurityPlugin"},
        {"type": "remove_use", "class_name": "ZedAgentMerchantUserSecurityPlugin"},
        {"type": "remove_use", "class_name": "MultiFactorAuthenticationMerchantUserSecurityPlugin"},
        {"type": "remove_use", "class_name": "ZedMerchantUserSecurityPlugin"},
        {"type": "remove_plugin", "plugin_class": "ZedAgentMerchantUserSecurityPlugin"},
        {"type": "remove_plugin", "plugin_class": "ZedMerchantUserSecurityPlugin"},
        {"type": "remove_plugin", "plugin_class": "MultiFactorAuthenticationMerchantUserSecurityPlugin"},
        {"type": "remove_plugin", "plugin_class": "MultiFactorAuthenticationAgentMerchantUserSecurityPlugin"}
    ],
    "success_messages": [
        "✓ SecurityDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed ZedAgentMerchantUserSecurityPlugin",
        "✓ Removed ZedMerchantUserSecurityPlugin",
        "✓ Removed MultiFactorAuthenticationMerchantUserSecurityPlugin",
        "✓ Removed MultiFactorAuthenticationAgentMerchantUserSecurityPlugin"
    ]
}'
clean_php_file "$SECURITY_DEP_FILE" "$CONFIG_JSON" "SecurityDependencyProvider"
echo ""

echo "Step 40: Removing marketplace-specific plugins from SecurityGuiDependencyProvider..."
SECURITY_GUI_DEP_FILE="src/Pyz/Zed/SecurityGui/SecurityGuiDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/SecurityGui/SecurityGuiDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantUserUserRoleFilterPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantUserUserRoleFilterPlugin"}
    ],
    "success_messages": [
        "✓ SecurityGuiDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantUserUserRoleFilterPlugin"
    ]
}'
clean_php_file "$SECURITY_GUI_DEP_FILE" "$CONFIG_JSON" "SecurityGuiDependencyProvider"
echo ""

echo "Step 41: Removing marketplace-specific plugins from ShipmentDependencyProvider..."
SHIPMENT_DEP_FILE="src/Pyz/Zed/Shipment/ShipmentDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Shipment/ShipmentDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantReferenceShipmentExpenseExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantReferenceShipmentExpenseExpanderPlugin"}
    ],
    "success_messages": [
        "✓ ShipmentDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantReferenceShipmentExpenseExpanderPlugin"
    ]
}'
clean_php_file "$SHIPMENT_DEP_FILE" "$CONFIG_JSON" "ShipmentDependencyProvider"
echo ""

echo "Step 42: Removing marketplace-specific plugins from ShipmentsRestApiDependencyProvider..."
SHIPMENTS_REST_API_DEP_FILE="src/Pyz/Zed/ShipmentsRestApi/ShipmentsRestApiDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/ShipmentsRestApi/ShipmentsRestApiDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantReferenceQuoteItemExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantReferenceQuoteItemExpanderPlugin"}
    ],
    "success_messages": [
        "✓ ShipmentsRestApiDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantReferenceQuoteItemExpanderPlugin"
    ]
}'
clean_php_file "$SHIPMENTS_REST_API_DEP_FILE" "$CONFIG_JSON" "ShipmentsRestApiDependencyProvider"
echo ""

echo "Step 43: Removing marketplace-specific plugins from ShipmentGuiDependencyProvider..."
SHIPMENT_GUI_DEP_FILE="src/Pyz/Zed/ShipmentGui/ShipmentGuiDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/ShipmentGui/ShipmentGuiDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantShipmentOrderItemTemplatePlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantShipmentOrderItemTemplatePlugin"}
    ],
    "success_messages": [
        "✓ ShipmentGuiDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantShipmentOrderItemTemplatePlugin"
    ]
}'
clean_php_file "$SHIPMENT_GUI_DEP_FILE" "$CONFIG_JSON" "ShipmentGuiDependencyProvider"
echo ""

echo "Step 44: Removing marketplace-specific plugins from ShoppingListDependencyProvider..."
SHOPPING_LIST_DEP_FILE="src/Pyz/Zed/ShoppingList/ShoppingListDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/ShoppingList/ShoppingListDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantProductAddItemPreCheckPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductShoppingListItemBulkPostSavePlugin"},
        {"type": "remove_use", "class_name": "MerchantProductShoppingListItemCollectionExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductAddItemPreCheckPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductShoppingListItemBulkPostSavePlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductShoppingListItemCollectionExpanderPlugin"}
    ],
    "success_messages": [
        "✓ ShoppingListDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantProductAddItemPreCheckPlugin",
        "✓ Removed MerchantProductShoppingListItemBulkPostSavePlugin",
        "✓ Removed MerchantProductShoppingListItemCollectionExpanderPlugin"
    ]
}'
clean_php_file "$SHOPPING_LIST_DEP_FILE" "$CONFIG_JSON" "ShoppingListDependencyProvider"
echo ""

echo "Step 45: Removing marketplace-specific plugins from StateMachineDependencyProvider..."
STATE_MACHINE_DEP_FILE="src/Pyz/Zed/StateMachine/StateMachineDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/StateMachine/StateMachineDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantStateMachineHandlerPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantStateMachineHandlerPlugin"}
    ],
    "success_messages": [
        "✓ StateMachineDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantStateMachineHandlerPlugin"
    ]
}'
clean_php_file "$STATE_MACHINE_DEP_FILE" "$CONFIG_JSON" "StateMachineDependencyProvider"
echo ""

echo "Step 46: Removing marketplace-specific plugins from SynchronizationDependencyProvider..."
SYNCHRONIZATION_DEP_FILE="src/Pyz/Zed/Synchronization/SynchronizationDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Synchronization/SynchronizationDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantOpeningHoursSynchronizationDataBulkPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantOpeningHoursSynchronizationDataBulkPlugin"}
    ],
    "success_messages": [
        "✓ SynchronizationDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantOpeningHoursSynchronizationDataBulkPlugin"
    ]
}'
clean_php_file "$SYNCHRONIZATION_DEP_FILE" "$CONFIG_JSON" "SynchronizationDependencyProvider"
echo ""

echo "Step 47: Removing marketplace-specific plugins from TwigDependencyProvider..."
TWIG_DEP_FILE="src/Pyz/Zed/Twig/TwigDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/Twig/TwigDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantNavigationTypeTwigPlugin"},
        {"type": "remove_use", "class_name": "MerchantUserTwigPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantUserTwigPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantNavigationTypeTwigPlugin"}
    ],
    "success_messages": [
        "✓ TwigDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantUserTwigPlugin",
        "✓ Removed MerchantNavigationTypeTwigPlugin"
    ]
}'
clean_php_file "$TWIG_DEP_FILE" "$CONFIG_JSON" "TwigDependencyProvider"
echo ""

echo "Step 48: Removing marketplace-specific plugins from UserDependencyProvider..."
USER_DEP_FILE="src/Pyz/Zed/User/UserDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/User/UserDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantAgentUserQueryCriteriaExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantAgentUserFormExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantAgentUserTableConfigExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantAgentUserTableDataExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantAgentUserFormExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantAgentUserTableConfigExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantAgentUserTableDataExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantAgentUserQueryCriteriaExpanderPlugin"}
    ],
    "success_messages": [
        "✓ UserDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantAgentUserFormExpanderPlugin",
        "✓ Removed MerchantAgentUserTableConfigExpanderPlugin",
        "✓ Removed MerchantAgentUserTableDataExpanderPlugin",
        "✓ Removed MerchantAgentUserQueryCriteriaExpanderPlugin"
    ]
}'
clean_php_file "$USER_DEP_FILE" "$CONFIG_JSON" "UserDependencyProvider"
echo ""

echo "Step 49: Removing marketplace-specific plugins from ZedNavigationDependencyProvider..."
ZED_NAVIGATION_DEP_FILE="src/Pyz/Zed/ZedNavigation/ZedNavigationDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/ZedNavigation/ZedNavigationDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "AgentMerchantPortalNavigationItemCollectionFilterPlugin"},
        {"type": "remove_use", "class_name": "MerchantPortalNavigationItemCollectionFilterPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantPortalNavigationItemCollectionFilterPlugin"},
        {"type": "remove_plugin", "plugin_class": "AgentMerchantPortalNavigationItemCollectionFilterPlugin"}
    ],
    "success_messages": [
        "✓ ZedNavigationDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantPortalNavigationItemCollectionFilterPlugin",
        "✓ Removed AgentMerchantPortalNavigationItemCollectionFilterPlugin"
    ]
}'
clean_php_file "$ZED_NAVIGATION_DEP_FILE" "$CONFIG_JSON" "ZedNavigationDependencyProvider"
echo ""

echo "Step 50: Removing marketplace-specific configuration from ZedNavigationConfig..."
ZED_NAVIGATION_CONFIG_FILE="src/Pyz/Zed/ZedNavigation/ZedNavigationConfig.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Zed/ZedNavigation/ZedNavigationConfig.php",
    "operations": [
        {"type": "remove_constant", "constant_name": "NAVIGATION_TYPE_MAIN_MERCHANT_PORTAL"},
        {"type": "remove_constant", "constant_name": "NAVIGATION_TYPE_SECONDARY_MERCHANT_PORTAL"},
        {"type": "remove_method", "method_name": "getCacheFilePaths"},
        {"type": "remove_method", "method_name": "getRootNavigationSchemaPaths"},
        {"type": "remove_method", "method_name": "getNavigationSchemaFileNamePatterns"},
        {"type": "remove_method", "method_name": "getDefaultNavigationType"}
    ],
    "success_messages": [
        "✓ ZedNavigationConfig cleaned from marketplace-specific configuration",
        "✓ Removed NAVIGATION_TYPE_MAIN_MERCHANT_PORTAL constant",
        "✓ Removed NAVIGATION_TYPE_SECONDARY_MERCHANT_PORTAL constant",
        "✓ Removed merchant portal navigation configuration methods"
    ]
}'
clean_php_file "$ZED_NAVIGATION_CONFIG_FILE" "$CONFIG_JSON" "ZedNavigationConfig"
echo ""

echo "Step 51: Removing marketplace-specific plugins from Yves CartPageDependencyProvider..."
YVES_CART_PAGE_DEP_FILE="src/Pyz/Yves/CartPage/CartPageDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Yves/CartPage/CartPageDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantProductPreAddToCartPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductPreAddToCartPlugin"}
    ],
    "success_messages": [
        "✓ Yves CartPageDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantProductPreAddToCartPlugin",
        "✓ Removed MerchantProductOfferPreAddToCartPlugin"
    ]
}'
clean_php_file "$YVES_CART_PAGE_DEP_FILE" "$CONFIG_JSON" "Yves CartPageDependencyProvider"
echo ""

echo "Step 52: Removing marketplace-specific plugins from Yves CheckoutPageDependencyProvider..."
YVES_CHECKOUT_PAGE_DEP_FILE="src/Pyz/Yves/CheckoutPage/CheckoutPageDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Yves/CheckoutPage/CheckoutPageDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "DummyMarketplacePaymentConfig"},
        {"type": "remove_use", "class_name": "DummyMarketplacePaymentHandlerPlugin"},
        {"type": "remove_use", "class_name": "DummyMarketplacePaymentInvoiceSubFormPlugin"},
        {"type": "remove_use", "class_name": "MerchantShipmentCheckoutPageStepEnginePreRenderPlugin"},
        {"type": "remove_collection_add", "plugin_class": "DummyMarketplacePaymentHandlerPlugin"},
        {"type": "remove_collection_add", "plugin_class": "DummyMarketplacePaymentInvoiceSubFormPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantShipmentCheckoutPageStepEnginePreRenderPlugin"}
    ],
    "success_messages": [
        "✓ Yves CheckoutPageDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed DummyMarketplacePaymentHandlerPlugin",
        "✓ Removed DummyMarketplacePaymentInvoiceSubFormPlugin",
        "✓ Removed MerchantShipmentCheckoutPageStepEnginePreRenderPlugin"
    ]
}'
clean_php_file "$YVES_CHECKOUT_PAGE_DEP_FILE" "$CONFIG_JSON" "Yves CheckoutPageDependencyProvider"
echo ""

echo "Step 53: Removing marketplace-specific plugins from Yves CustomerPageDependencyProvider..."
YVES_CUSTOMER_PAGE_DEP_FILE="src/Pyz/Yves/CustomerPage/CustomerPageDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Yves/CustomerPage/CustomerPageDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantShipmentCheckoutAddressStepPreGroupItemsByShipmentPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantShipmentCheckoutAddressStepPreGroupItemsByShipmentPlugin"}
    ],
    "success_messages": [
        "✓ Yves CustomerPageDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantShipmentCheckoutAddressStepPreGroupItemsByShipmentPlugin"
    ]
}'
clean_php_file "$YVES_CUSTOMER_PAGE_DEP_FILE" "$CONFIG_JSON" "Yves CustomerPageDependencyProvider"
echo ""

echo "Step 54: Removing marketplace-specific plugins from Yves QuickOrderPageDependencyProvider..."
YVES_QUICK_ORDER_PAGE_DEP_FILE="src/Pyz/Yves/QuickOrderPage/QuickOrderPageDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Yves/QuickOrderPage/QuickOrderPageDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantProductQuickOrderItemExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantQuickOrderItemMapperPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductQuickOrderItemExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantQuickOrderItemMapperPlugin"}
    ],
    "success_messages": [
        "✓ Yves QuickOrderPageDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantProductQuickOrderItemExpanderPlugin",
        "✓ Removed MerchantQuickOrderItemMapperPlugin"
    ]
}'
clean_php_file "$YVES_QUICK_ORDER_PAGE_DEP_FILE" "$CONFIG_JSON" "Yves QuickOrderPageDependencyProvider"
echo ""

echo "Step 55: Removing marketplace-specific plugins from Yves RouterDependencyProvider..."
YVES_ROUTER_DEP_FILE="src/Pyz/Yves/Router/RouterDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Yves/Router/RouterDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantRegistrationRequestPageRouteProviderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantRegistrationRequestPageRouteProviderPlugin"}
    ],
    "success_messages": [
        "✓ Yves RouterDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantRegistrationRequestPageRouteProviderPlugin"
    ]
}'
clean_php_file "$YVES_ROUTER_DEP_FILE" "$CONFIG_JSON" "Yves RouterDependencyProvider"
echo ""

echo "Step 56: Removing marketplace-specific plugins from Yves StorageRouterDependencyProvider..."
YVES_STORAGE_ROUTER_DEP_FILE="src/Pyz/Yves/StorageRouter/StorageRouterDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Yves/StorageRouter/StorageRouterDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantPageResourceCreatorPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantPageResourceCreatorPlugin"}
    ],
    "success_messages": [
        "✓ Yves StorageRouterDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantPageResourceCreatorPlugin"
    ]
}'
clean_php_file "$YVES_STORAGE_ROUTER_DEP_FILE" "$CONFIG_JSON" "Yves StorageRouterDependencyProvider"
echo ""

echo "Step 57: Removing marketplace-specific plugins from Glue CartsRestApiDependencyProvider..."
GLUE_CARTS_REST_API_DEP_FILE="src/Pyz/Glue/CartsRestApi/CartsRestApiDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Glue/CartsRestApi/CartsRestApiDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantProductCartItemExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductCartItemExpanderPlugin"}
    ],
    "success_messages": [
        "✓ Glue CartsRestApiDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantProductCartItemExpanderPlugin"
    ]
}'
clean_php_file "$GLUE_CARTS_REST_API_DEP_FILE" "$CONFIG_JSON" "Glue CartsRestApiDependencyProvider"
echo ""

echo "Step 58: Removing marketplace-specific plugins from Glue GlueApplicationDependencyProvider..."
GLUE_APPLICATION_DEP_FILE="src/Pyz/Glue/GlueApplication/GlueApplicationDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Glue/GlueApplication/GlueApplicationDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantOpeningHoursRestApiConfig"},
        {"type": "remove_use", "class_name": "MerchantOpeningHoursByMerchantReferenceResourceRelationshipPlugin"},
        {"type": "remove_use", "class_name": "MerchantOpeningHoursResourceRoutePlugin"},
        {"type": "remove_use", "class_name": "MerchantProductOffersRestApiConfig"},
        {"type": "remove_use", "class_name": "ConcreteProductsProductOffersResourceRoutePlugin"},
        {"type": "remove_use", "class_name": "ProductOffersByProductConcreteSkuResourceRelationshipPlugin"},
        {"type": "remove_use", "class_name": "ProductOffersResourceRoutePlugin"},
        {"type": "remove_use", "class_name": "MerchantsRestApiConfig"},
        {"type": "remove_use", "class_name": "MerchantAddressByMerchantReferenceResourceRelationshipPlugin"},
        {"type": "remove_use", "class_name": "MerchantAddressesResourceRoutePlugin"},
        {"type": "remove_use", "class_name": "MerchantByMerchantReferenceResourceRelationshipPlugin"},
        {"type": "remove_use", "class_name": "MerchantsByOrderResourceRelationshipPlugin"},
        {"type": "remove_use", "class_name": "MerchantsResourceRoutePlugin"},
        {"type": "remove_use", "class_name": "ProductOfferAvailabilitiesRestApiConfig"},
        {"type": "remove_use", "class_name": "ProductOfferAvailabilitiesByProductOfferReferenceResourceRelationshipPlugin"},
        {"type": "remove_use", "class_name": "ProductOfferAvailabilitiesResourceRoutePlugin"},
        {"type": "remove_use", "class_name": "ProductOfferPricesRestApiConfig"},
        {"type": "remove_use", "class_name": "ProductOfferPriceByProductOfferReferenceResourceRelationshipPlugin"},
        {"type": "remove_use", "class_name": "ProductOfferPricesResourceRoutePlugin"},
        {"type": "remove_use", "class_name": "ProductOffersByProductOfferReferenceResourceRelationshipPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantsResourceRoutePlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantAddressesResourceRoutePlugin"},
        {"type": "remove_plugin", "plugin_class": "ProductOffersResourceRoutePlugin"},
        {"type": "remove_plugin", "plugin_class": "ConcreteProductsProductOffersResourceRoutePlugin"},
        {"type": "remove_plugin", "plugin_class": "ProductOfferAvailabilitiesResourceRoutePlugin"},
        {"type": "remove_plugin", "plugin_class": "ProductOfferPricesResourceRoutePlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantOpeningHoursResourceRoutePlugin"},
        {"type": "remove_resource_relationship", "resource_type": "MerchantsRestApiConfig::RESOURCE_MERCHANTS", "plugin_class": "MerchantAddressByMerchantReferenceResourceRelationshipPlugin"},
        {"type": "remove_resource_relationship", "resource_type": "OrdersRestApiConfig::RESOURCE_ORDERS", "plugin_class": "MerchantsByOrderResourceRelationshipPlugin"},
        {"type": "remove_resource_relationship", "resource_type": "ProductsRestApiConfig::RESOURCE_ABSTRACT_PRODUCTS", "plugin_class": "MerchantByMerchantReferenceResourceRelationshipPlugin"},
        {"type": "remove_resource_relationship", "resource_type": "CartsRestApiConfig::RESOURCE_CART_ITEMS", "plugin_class": "MerchantByMerchantReferenceResourceRelationshipPlugin"},
        {"type": "remove_resource_relationship", "resource_type": "CartsRestApiConfig::RESOURCE_GUEST_CARTS_ITEMS", "plugin_class": "MerchantByMerchantReferenceResourceRelationshipPlugin"},
        {"type": "remove_resource_relationship", "resource_type": "ProductsRestApiConfig::RESOURCE_CONCRETE_PRODUCTS", "plugin_class": "ProductOffersByProductConcreteSkuResourceRelationshipPlugin"},
        {"type": "remove_resource_relationship", "resource_type": "MerchantProductOffersRestApiConfig::RESOURCE_PRODUCT_OFFERS", "plugin_class": "MerchantByMerchantReferenceResourceRelationshipPlugin"},
        {"type": "remove_resource_relationship", "resource_type": "MerchantProductOffersRestApiConfig::RESOURCE_PRODUCT_OFFERS", "plugin_class": "ProductOfferPriceByProductOfferReferenceResourceRelationshipPlugin"},
        {"type": "remove_resource_relationship", "resource_type": "MerchantsRestApiConfig::RESOURCE_MERCHANTS", "plugin_class": "MerchantOpeningHoursByMerchantReferenceResourceRelationshipPlugin"},
        {"type": "remove_resource_relationship", "resource_type": "SalesReturnsRestApiConfig::RESOURCE_RETURNS", "plugin_class": "MerchantByMerchantReferenceResourceRelationshipPlugin"},
        {"type": "remove_resource_relationship", "resource_type": "ShoppingListsRestApiConfig::RESOURCE_SHOPPING_LIST_ITEMS", "plugin_class": "MerchantByMerchantReferenceResourceRelationshipPlugin"},
        {"type": "remove_resource_relationship", "resource_type": "MerchantProductOffersRestApiConfig::RESOURCE_PRODUCT_OFFERS", "plugin_class": "ProductOfferAvailabilitiesByProductOfferReferenceResourceRelationshipPlugin"},
        {"type": "remove_resource_relationship", "resource_type": "ShoppingListsRestApiConfig::RESOURCE_SHOPPING_LIST_ITEMS", "plugin_class": "ProductOfferAvailabilitiesByProductOfferReferenceResourceRelationshipPlugin"},
        {"type": "remove_resource_relationship", "resource_type": "ShoppingListsRestApiConfig::RESOURCE_SHOPPING_LIST_ITEMS", "plugin_class": "ProductOffersByProductOfferReferenceResourceRelationshipPlugin"},
        {"type": "remove_resource_relationship", "resource_type": "ShoppingListsRestApiConfig::RESOURCE_SHOPPING_LIST_ITEMS", "plugin_class": "ProductOfferPriceByProductOfferReferenceResourceRelationshipPlugin"}
    ],
    "success_messages": [
        "✓ Glue GlueApplicationDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantsResourceRoutePlugin",
        "✓ Removed MerchantAddressesResourceRoutePlugin",
        "✓ Removed MerchantOpeningHoursResourceRoutePlugin",
        "✓ Removed product offer related plugins",
        "✓ Removed all MerchantByMerchantReferenceResourceRelationshipPlugin registrations",
        "✓ Removed all MerchantOpeningHoursByMerchantReferenceResourceRelationshipPlugin registrations",
        "✓ Removed all product offer resource relationships"
    ]
}'
clean_php_file "$GLUE_APPLICATION_DEP_FILE" "$CONFIG_JSON" "Glue GlueApplicationDependencyProvider"
echo ""

echo "Step 59: Removing marketplace-specific plugins from Glue UrlsRestApiDependencyProvider..."
GLUE_URLS_REST_API_DEP_FILE="src/Pyz/Glue/UrlsRestApi/UrlsRestApiDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Glue/UrlsRestApi/UrlsRestApiDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantRestUrlResolverAttributesTransferProviderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantRestUrlResolverAttributesTransferProviderPlugin"}
    ],
    "success_messages": [
        "✓ Glue UrlsRestApiDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantRestUrlResolverAttributesTransferProviderPlugin"
    ]
}'
clean_php_file "$GLUE_URLS_REST_API_DEP_FILE" "$CONFIG_JSON" "Glue UrlsRestApiDependencyProvider"
echo ""

echo "Step 60: Removing marketplace-specific plugins from Client CatalogDependencyProvider..."
CLIENT_CATALOG_DEP_FILE="src/Pyz/Client/Catalog/CatalogDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Client/Catalog/CatalogDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantReferenceQueryExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductReferenceQueryExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantReferenceQueryExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductReferenceQueryExpanderPlugin"}
    ],
    "success_messages": [
        "✓ Client CatalogDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantReferenceQueryExpanderPlugin",
        "✓ Removed MerchantProductReferenceQueryExpanderPlugin"
    ]
}'
clean_php_file "$CLIENT_CATALOG_DEP_FILE" "$CONFIG_JSON" "Client CatalogDependencyProvider"
echo ""

echo "Step 61: Removing marketplace-specific plugins from Client ProductStorageDependencyProvider..."
CLIENT_PRODUCT_STORAGE_DEP_FILE="src/Pyz/Client/ProductStorage/ProductStorageDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Client/ProductStorage/ProductStorageDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "ProductViewMerchantProductExpanderPlugin"},
        {"type": "remove_use", "class_name": "ProductViewProductOfferExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "ProductViewMerchantProductExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "ProductViewProductOfferExpanderPlugin"}
    ],
    "success_messages": [
        "✓ Client ProductStorageDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed ProductViewMerchantProductExpanderPlugin",
        "✓ Removed ProductViewProductOfferExpanderPlugin"
    ]
}'
clean_php_file "$CLIENT_PRODUCT_STORAGE_DEP_FILE" "$CONFIG_JSON" "Client ProductStorageDependencyProvider"
echo ""

echo "Step 62: Removing marketplace-specific queue configurations from Client RabbitMqConfig..."
CLIENT_RABBITMQ_CONFIG_FILE="src/Pyz/Client/RabbitMq/RabbitMqConfig.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Client/RabbitMq/RabbitMqConfig.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantOpeningHoursStorageConfig"},
        {"type": "remove_array_constant_entry", "constant_reference": "MerchantOpeningHoursStorageConfig::MERCHANT_OPENING_HOURS_SYNC_STORAGE_QUEUE"}
    ],
    "success_messages": [
        "✓ Client RabbitMqConfig cleaned from marketplace-specific queue configurations",
        "✓ Removed MerchantOpeningHoursStorageConfig queue entries"
    ]
}'
clean_php_file "$CLIENT_RABBITMQ_CONFIG_FILE" "$CONFIG_JSON" "Client RabbitMqConfig"
echo ""

echo "Step 63: Removing marketplace-specific plugins from Client SearchDependencyProvider..."
CLIENT_SEARCH_DEP_FILE="src/Pyz/Client/Search/SearchDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Client/Search/SearchDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantNameSearchConfigExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductMerchantNameSearchConfigExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductMerchantNameSearchConfigExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantNameSearchConfigExpanderPlugin"}
    ],
    "success_messages": [
        "✓ Client SearchDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantProductMerchantNameSearchConfigExpanderPlugin",
        "✓ Removed MerchantNameSearchConfigExpanderPlugin"
    ]
}'
clean_php_file "$CLIENT_SEARCH_DEP_FILE" "$CONFIG_JSON" "Client SearchDependencyProvider"
echo ""

echo "Step 64: Removing marketplace-specific plugins from Client SearchElasticsearchDependencyProvider..."
CLIENT_SEARCH_ELASTICSEARCH_DEP_FILE="src/Pyz/Client/SearchElasticsearch/SearchElasticsearchDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Client/SearchElasticsearch/SearchElasticsearchDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantNameSearchConfigExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantProductMerchantNameSearchConfigExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantProductMerchantNameSearchConfigExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantNameSearchConfigExpanderPlugin"}
    ],
    "success_messages": [
        "✓ Client SearchElasticsearchDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantProductMerchantNameSearchConfigExpanderPlugin",
        "✓ Removed MerchantNameSearchConfigExpanderPlugin"
    ]
}'
clean_php_file "$CLIENT_SEARCH_ELASTICSEARCH_DEP_FILE" "$CONFIG_JSON" "Client SearchElasticsearchDependencyProvider"
echo ""

echo "Step 65: Removing marketplace-specific plugins from Client SecurityBlockerDependencyProvider..."
CLIENT_SECURITY_BLOCKER_DEP_FILE="src/Pyz/Client/SecurityBlocker/SecurityBlockerDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Client/SecurityBlocker/SecurityBlockerDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "AgentMerchantPortalSecurityBlockerConfigurationSettingsExpanderPlugin"},
        {"type": "remove_use", "class_name": "MerchantPortalUserSecurityBlockerConfigurationSettingsExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantPortalUserSecurityBlockerConfigurationSettingsExpanderPlugin"},
        {"type": "remove_plugin", "plugin_class": "AgentMerchantPortalSecurityBlockerConfigurationSettingsExpanderPlugin"}
    ],
    "success_messages": [
        "✓ Client SecurityBlockerDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantPortalUserSecurityBlockerConfigurationSettingsExpanderPlugin",
        "✓ Removed AgentMerchantPortalSecurityBlockerConfigurationSettingsExpanderPlugin"
    ]
}'
clean_php_file "$CLIENT_SECURITY_BLOCKER_DEP_FILE" "$CONFIG_JSON" "Client SecurityBlockerDependencyProvider"
echo ""

echo "Step 66: Removing marketplace-specific plugins from Client ShoppingListDependencyProvider..."
CLIENT_SHOPPING_LIST_DEP_FILE="src/Pyz/Client/ShoppingList/ShoppingListDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Client/ShoppingList/ShoppingListDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantShoppingListItemToItemMapperPlugin"},
        {"type": "remove_plugin", "plugin_class": "MerchantShoppingListItemToItemMapperPlugin"}
    ],
    "success_messages": [
        "✓ Client ShoppingListDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed MerchantShoppingListItemToItemMapperPlugin"
    ]
}'
clean_php_file "$CLIENT_SHOPPING_LIST_DEP_FILE" "$CONFIG_JSON" "Client ShoppingListDependencyProvider"
echo ""

echo "Step 67: Removing marketplace-specific plugins from Client UrlStorageDependencyProvider..."
CLIENT_URL_STORAGE_DEP_FILE="src/Pyz/Client/UrlStorage/UrlStorageDependencyProvider.php"
CONFIG_JSON='{
    "file_path": "src/Pyz/Client/UrlStorage/UrlStorageDependencyProvider.php",
    "operations": [
        {"type": "remove_use", "class_name": "UrlStorageMerchantMapperPlugin"},
        {"type": "remove_plugin", "plugin_class": "UrlStorageMerchantMapperPlugin"}
    ],
    "success_messages": [
        "✓ Client UrlStorageDependencyProvider cleaned from marketplace-specific plugins",
        "✓ Removed UrlStorageMerchantMapperPlugin"
    ]
}'
clean_php_file "$CLIENT_URL_STORAGE_DEP_FILE" "$CONFIG_JSON" "Client UrlStorageDependencyProvider"
echo ""

echo "Step 68: Removing marketplace directories..."
for dir_entry in "${DIRECTORIES_TO_REMOVE[@]}"; do
    IFS=':' read -r dir_path dir_name <<< "$dir_entry"
    remove_directory "$dir_path" "$dir_name"
done
echo ""

echo "Step 69: Removing merchant-portal commands from docker.yml..."
DOCKER_YML_FILE="config/install/docker.yml"

cat > /tmp/remove_docker_commands.py << 'PYTHON_SCRIPT'
import sys
import re

def remove_docker_commands(content, commands_to_remove):
    """Remove specific command blocks from docker.yml."""
    lines = content.split('\n')
    result = []
    skip_until_indent = None
    current_indent = None
    
    i = 0
    while i < len(lines):
        line = lines[i]
        
        # Check if we should stop skipping
        if skip_until_indent is not None:
            # Calculate current line's indentation
            stripped = line.lstrip()
            if stripped:  # Non-empty line
                line_indent = len(line) - len(stripped)
                # Stop skipping when we reach same or less indentation
                if line_indent <= skip_until_indent:
                    skip_until_indent = None
                else:
                    i += 1
                    continue
            else:
                # Empty line while skipping
                i += 1
                continue
        
        # Check if this line starts a command block we want to remove
        stripped = line.lstrip()
        should_remove = False
        
        for cmd in commands_to_remove:
            if stripped.startswith(f'{cmd}:'):
                should_remove = True
                # Calculate indentation level to know when to stop skipping
                current_indent = len(line) - len(stripped)
                skip_until_indent = current_indent
                break
        
        if not should_remove:
            result.append(line)
        
        i += 1
    
    # Clean up multiple consecutive empty lines
    cleaned = []
    prev_empty = False
    for line in result:
        is_empty = line.strip() == ''
        if is_empty and prev_empty:
            continue
        cleaned.append(line)
        prev_empty = is_empty
    
    return '\n'.join(cleaned)

def process_docker_yml(file_path, commands):
    """Process docker.yml to remove specified commands."""
    try:
        with open(file_path, 'r') as f:
            content = f.read()
        
        # Check if any commands exist
        found = False
        for cmd in commands:
            if f'{cmd}:' in content:
                found = True
                break
        
        if not found:
            return False
        
        # Remove commands
        new_content = remove_docker_commands(content, commands)
        
        # Write back if changed
        if new_content != content:
            with open(file_path, 'w') as f:
                f.write(new_content)
            return True
        return False
    except FileNotFoundError:
        return None

if __name__ == '__main__':
    file_path = sys.argv[1]
    commands = sys.argv[2].split(',')
    result = process_docker_yml(file_path, commands)
    if result is True:
        print(f"✓ Removed merchant portal commands from {file_path}")
    elif result is False:
        print(f"⚠ No merchant portal commands found in {file_path}")
    else:
        print(f"⚠ File not found: {file_path}")
PYTHON_SCRIPT

if [ -f "$DOCKER_YML_FILE" ]; then
    python3 /tmp/remove_docker_commands.py "$DOCKER_YML_FILE" "router-cache-warmup-merchant-portal,merchant-portal-build-frontend,acl-entity-metadata-validate"
else
    echo "⚠ docker.yml file not found at $DOCKER_YML_FILE"
fi
echo ""

echo "Step 70: Removing merchant-portal applications from deploy files..."
DEPLOY_FILES=(
    "deploy.dev.yml"
    "deploy.yml"
)

cat > /tmp/remove_merchant_portal_apps.py << 'PYTHON_SCRIPT'
import sys
import re

def remove_merchant_portal_applications(content):
    """Remove merchant-portal application blocks from YAML using indentation-aware parsing."""
    lines = content.split('\n')
    result = []
    skip_until_indent = None
    
    i = 0
    while i < len(lines):
        line = lines[i]
        stripped = line.lstrip()
        
        # Check if we should stop skipping
        if skip_until_indent is not None:
            if stripped:  # Non-empty line
                line_indent = len(line) - len(stripped)
                # Stop skipping when we reach same or less indentation
                if line_indent <= skip_until_indent:
                    skip_until_indent = None
                else:
                    i += 1
                    continue
            else:
                # Empty line while skipping
                i += 1
                continue
        
        # Check if this line starts a merchant portal application block
        should_remove = False
        
        # Match mportal_* applications (like mportal_eu, mportal_us)
        if re.match(r'mportal_\w+:', stripped):
            should_remove = True
            current_indent = len(line) - len(stripped)
            skip_until_indent = current_indent
        # Check if this is an application definition with "application: merchant-portal" on next lines
        elif re.match(r'[a-z_]+:\s*(?:#.*)?$', stripped):
            # Look ahead to see if next non-empty line is "application: merchant-portal"
            j = i + 1
            while j < len(lines):
                next_line = lines[j]
                next_stripped = next_line.lstrip()
                if next_stripped:
                    if 'application:' in next_stripped and 'merchant-portal' in next_stripped:
                        should_remove = True
                        current_indent = len(line) - len(stripped)
                        skip_until_indent = current_indent
                    break
                j += 1
        
        if not should_remove:
            result.append(line)
        
        i += 1
    
    # Clean up multiple consecutive empty lines
    cleaned = []
    prev_empty = False
    for line in result:
        is_empty = line.strip() == ''
        if is_empty and prev_empty:
            continue
        cleaned.append(line)
        prev_empty = is_empty
    
    return '\n'.join(cleaned)

def process_deploy_file(file_path):
    """Process a deploy YAML file to remove merchant portal applications."""
    try:
        with open(file_path, 'r') as f:
            content = f.read()
        
        # Check if file contains merchant-portal
        if 'merchant-portal' not in content and 'mportal_' not in content:
            return False
        
        # Remove merchant portal applications
        new_content = remove_merchant_portal_applications(content)
        
        # Write back if changed
        if new_content != content:
            with open(file_path, 'w') as f:
                f.write(new_content)
            return True
        return False
    except FileNotFoundError:
        return None

if __name__ == '__main__':
    file_path = sys.argv[1]
    result = process_deploy_file(file_path)
    if result is True:
        print(f"✓ Removed merchant-portal applications from {file_path}")
    elif result is False:
        print(f"⚠ No merchant-portal applications found in {file_path}")
    else:
        print(f"⚠ File not found: {file_path}")
PYTHON_SCRIPT

for deploy_file in "${DEPLOY_FILES[@]}"; do
    if [ -f "$deploy_file" ]; then
        python3 /tmp/remove_merchant_portal_apps.py "$deploy_file"
    else
        echo "⚠ Deploy file not found: $deploy_file"
    fi
done
echo ""

# Create reusable Python config cleanup script
create_config_cleanup_script() {
    cat > /tmp/config_cleanup.py << 'PYTHON_SCRIPT'
import re
import sys
import json

def read_file(file_path):
    """Read file content."""
    with open(file_path, 'r') as f:
        return f.read()

def write_file(file_path, content):
    """Write content to file."""
    with open(file_path, 'w') as f:
        f.write(content)

def remove_use_statement(content, class_name):
    """Remove use statement for a specific class."""
    pattern = rf'use\s+[^;]*\\{re.escape(class_name)};\s*'
    content = re.sub(pattern, '', content)
    return content

def remove_config_assignment(content, constant_pattern):
    """Remove config assignment line (including multi-line assignments)."""
    pattern = rf'^\s*\$config\[{re.escape(constant_pattern)}[^\]]*\]\s*=.*?;\s*$'
    content = re.sub(pattern, '', content, flags=re.MULTILINE | re.DOTALL)
    return content

def remove_array_value(content, array_value):
    """Remove array value from arrays (like dependency injector arrays)."""
    # Match: 'value',
    pattern = rf"\s*'{re.escape(array_value)}',\s*"
    content = re.sub(pattern, '', content)
    return content

def remove_filesystem_config(content, filesystem_name):
    """Remove filesystem configuration block."""
    # Match: 'filesystem-name' => [ ... ],
    pattern = rf"\s*'{re.escape(filesystem_name)}'\s*=>\s*\[[^\]]*\],\s*"
    content = re.sub(pattern, '', content, flags=re.DOTALL)
    return content

def remove_section_comment(content, comment_pattern):
    """Remove section comments."""
    pattern = rf'^.*{re.escape(comment_pattern)}.*$'
    content = re.sub(pattern, '', content, flags=re.MULTILINE)
    return content

def remove_array_key_value(content, key_pattern):
    """Remove array key-value pair (including multi-line values)."""
    # Match: KeyPattern => 'value', or KeyPattern => 'MarketplacePayment01',
    pattern = rf'\s*{re.escape(key_pattern)}\s*=>\s*[^,]+,\s*'
    content = re.sub(pattern, '', content, flags=re.MULTILINE | re.DOTALL)
    return content

def cleanup_content(content):
    """Clean up multiple empty lines and ensure single newline at end."""
    content = re.sub(r'\n{3,}', '\n\n', content)
    content = content.rstrip() + '\n'
    return content

def process_config_file(config):
    """Process configuration file based on configuration."""
    file_path = config['file_path']
    
    content = read_file(file_path)
    
    # Apply all removal operations
    for operation in config.get('operations', []):
        op_type = operation['type']
        
        if op_type == 'remove_use':
            content = remove_use_statement(content, operation['class_name'])
        elif op_type == 'remove_config_assignment':
            content = remove_config_assignment(content, operation['constant_pattern'])
        elif op_type == 'remove_array_value':
            content = remove_array_value(content, operation['array_value'])
        elif op_type == 'remove_filesystem_config':
            content = remove_filesystem_config(content, operation['filesystem_name'])
        elif op_type == 'remove_section_comment':
            content = remove_section_comment(content, operation['comment_pattern'])
        elif op_type == 'remove_array_key_value':
            content = remove_array_key_value(content, operation['key_pattern'])
    
    # Cleanup
    content = cleanup_content(content)
    
    # Write back
    write_file(file_path, content)
    
    # Print success messages
    for message in config.get('success_messages', []):
        print(message)

if __name__ == '__main__':
    config_json = sys.argv[1]
    config = json.loads(config_json)
    process_config_file(config)
PYTHON_SCRIPT
}

# Function to clean config file
clean_config_file() {
    local file_path=$1
    local config_json=$2
    
    if [ -f "$file_path" ]; then
        python3 /tmp/config_cleanup.py "$config_json"
    else
        echo "⚠ File not found at $file_path"
    fi
}

# Create the Python config cleanup script
create_config_cleanup_script

echo "Step 71: Removing merchant portal configuration from config_default.php..."
CONFIG_DEFAULT_FILE="config/Shared/config_default.php"
CONFIG_JSON='{
    "file_path": "config/Shared/config_default.php",
    "operations": [
        {"type": "remove_use", "class_name": "SecurityBlockerMerchantPortalConstants"},
        {"type": "remove_use", "class_name": "MerchantPortalConstants"},
        {"type": "remove_use", "class_name": "MerchantProductDataImportConstants"},
        {"type": "remove_use", "class_name": "MerchantProductOfferDataImportConstants"},
        {"type": "remove_use", "class_name": "DummyMarketplacePaymentConfig"},
        {"type": "remove_use", "class_name": "AgentSecurityBlockerMerchantPortalConstants"},
        {"type": "remove_config_assignment", "constant_pattern": "SecurityBlockerMerchantPortalConstants::"},
        {"type": "remove_config_assignment", "constant_pattern": "MerchantPortalConstants::BASE_URL_MP"},
        {"type": "remove_config_assignment", "constant_pattern": "MerchantPortalConstants::"},
        {"type": "remove_config_assignment", "constant_pattern": "MerchantProductDataImportConstants::"},
        {"type": "remove_config_assignment", "constant_pattern": "MerchantProductOfferDataImportConstants::"},
        {"type": "remove_config_assignment", "constant_pattern": "AgentSecurityBlockerMerchantPortalConstants::"},
        {"type": "remove_array_key_value", "key_pattern": "DummyMarketplacePaymentConfig::PAYMENT_METHOD_DUMMY_MARKETPLACE_PAYMENT_INVOICE"},
        {"type": "remove_filesystem_config", "filesystem_name": "merchant-product-data-import-files"},
        {"type": "remove_filesystem_config", "filesystem_name": "merchant-product-offer-data-import-files"},
        {"type": "remove_section_comment", "comment_pattern": "MERCHANT PORTAL"}
    ],
    "success_messages": [
        "✓ Removed merchant portal configuration from config_default.php"
    ]
}'
clean_config_file "$CONFIG_DEFAULT_FILE" "$CONFIG_JSON"
echo ""

echo "Step 72: Removing merchant portal configuration from config_default-docker.dev.php..."
CONFIG_DOCKER_DEV_FILE="config/Shared/config_default-docker.dev.php"
CONFIG_JSON='{
    "file_path": "config/Shared/config_default-docker.dev.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantPortalConstants"},
        {"type": "remove_config_assignment", "constant_pattern": "MerchantPortalConstants::BASE_URL_MP"},
        {"type": "remove_config_assignment", "constant_pattern": "MerchantPortalConstants::"},
        {"type": "remove_filesystem_config", "filesystem_name": "merchant-product-data-import-files"},
        {"type": "remove_filesystem_config", "filesystem_name": "merchant-product-offer-data-import-files"},
        {"type": "remove_section_comment", "comment_pattern": "MERCHANT PORTAL"}
    ],
    "success_messages": [
        "✓ Removed merchant portal configuration from config_default-docker.dev.php"
    ]
}'
clean_config_file "$CONFIG_DOCKER_DEV_FILE" "$CONFIG_JSON"
echo ""

echo "Step 73: Removing merchant portal configuration from config_default-ci.php..."
CONFIG_CI_FILE="config/Shared/config_default-ci.php"
CONFIG_JSON='{
    "file_path": "config/Shared/config_default-ci.php",
    "operations": [
        {"type": "remove_use", "class_name": "MerchantPortalConstants"},
        {"type": "remove_config_assignment", "constant_pattern": "MerchantPortalConstants::BASE_URL_MP"},
        {"type": "remove_config_assignment", "constant_pattern": "MerchantPortalConstants::"},
        {"type": "remove_filesystem_config", "filesystem_name": "merchant-product-data-import-files"},
        {"type": "remove_filesystem_config", "filesystem_name": "merchant-product-offer-data-import-files"},
        {"type": "remove_section_comment", "comment_pattern": "MERCHANT PORTAL"}
    ],
    "success_messages": [
        "✓ Removed merchant portal configuration from config_default-ci.php"
    ]
}'
clean_config_file "$CONFIG_CI_FILE" "$CONFIG_JSON"
echo ""

echo "Step 74: Removing marketplace payment configuration from config_oms-development.php..."
CONFIG_OMS_DEV_FILE="config/Shared/common/config_oms-development.php"
CONFIG_JSON='{
    "file_path": "config/Shared/common/config_oms-development.php",
    "operations": [
        {"type": "remove_use", "class_name": "DummyMarketplacePaymentConfig"},
        {"type": "remove_array_value", "array_value": "DummyMarketplacePayment"},
        {"type": "remove_array_value", "array_value": "MarketplacePayment01"},
        {"type": "remove_array_key_value", "key_pattern": "DummyMarketplacePaymentConfig::PAYMENT_METHOD_DUMMY_MARKETPLACE_PAYMENT_INVOICE"}
    ],
    "success_messages": [
        "✓ Removed marketplace payment configuration from config_oms-development.php"
    ]
}'
clean_config_file "$CONFIG_OMS_DEV_FILE" "$CONFIG_JSON"
echo ""

echo "Step 75: Running composer update to apply all changes..."
composer update --ignore-platform-req=ext-grpc
echo "✓ Composer update completed"
echo ""

echo "=========================================="
echo "Marketplace packages uninstalled successfully!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Review and update other configuration files"
echo "2. Check for remaining marketplace-specific code in src/Pyz"
echo "3. Update deploy files to remove marketplace references"
echo "4. Run 'composer install' to ensure consistency"
echo "5. Clear caches and rebuild"