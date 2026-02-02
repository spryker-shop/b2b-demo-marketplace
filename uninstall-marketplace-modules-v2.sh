#!/bin/bash

# Script to uninstall marketplace-specific packages from B2B Marketplace
# This version uses a centralized JSON configuration file for better maintainability
# Configuration file: uninstall-marketplace-config.json

set -e

echo "=========================================="
echo "Uninstalling Marketplace Features"
echo "=========================================="
echo ""

# Configuration file path
CONFIG_FILE="uninstall-marketplace-config.json"

if [ ! -f "$CONFIG_FILE" ]; then
    echo "❌ Configuration file not found: $CONFIG_FILE"
    echo "Please ensure $CONFIG_FILE exists in the current directory"
    exit 1
fi

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
    # "spryker-feature/marketplace-product"
    "spryker-feature/marketplace-product-approval-process"
    "spryker-feature/marketplace-product-options"
    "spryker-feature/marketplace-promotions-discounts"
    "spryker-feature/marketplace-return-management"
    "spryker-feature/marketplace-shopping-lists"
    "spryker-feature/merchant-category"
    "spryker-feature/merchant-opening-hours"
    "spryker-feature/merchant-portal-data-import"
    "spryker-feature/product-approval-process"
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
composer remove --ignore-platform-req=ext-grpc --ignore-platform-req=ext-redis "${MARKETPLACE_FEATURES[@]}"
echo "✓ Marketplace features marked for removal"
echo ""

echo "Step 2: Removing Marketplace Core Modules..."
composer remove --ignore-platform-req=ext-grpc --ignore-platform-req=ext-redis "${MARKETPLACE_CORE_MODULES[@]}"
echo "✓ Marketplace core modules marked for removal"
echo ""

# Create reusable Python cleanup script
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
    pattern = rf'use\s+[^;]*\\{re.escape(class_name)};\s*'
    content = re.sub(pattern, '', content)
    return content

def remove_plugin_from_array(content, plugin_class_name):
    """Remove plugin instantiation from array."""
    keyed_array_pattern = rf'\s*\$\w+\[[^\]]+\]\s*=\s*new\s+{re.escape(plugin_class_name)}\s*\([^)]*\);\s*'
    content = re.sub(keyed_array_pattern, '', content)
    array_push_pattern = rf'\s*\$\w+\[\]\s*=\s*new\s+{re.escape(plugin_class_name)}\s*\([^)]*\);\s*'
    content = re.sub(array_push_pattern, '', content)
    class_pattern = rf'\s*{re.escape(plugin_class_name)}::class,?\s*'
    content = re.sub(class_pattern, '', content)
    pattern = rf'\s*new\s+{re.escape(plugin_class_name)}\s*\([^)]*\),?\s*'
    content = re.sub(pattern, '', content)
    return content

def remove_event_subscriber(content, subscriber_class_name):
    """Remove event subscriber add() call."""
    pattern = rf'\s*\$\w+->add\(new\s+{re.escape(subscriber_class_name)}\s*\([^)]*\)\);\s*'
    content = re.sub(pattern, '', content)
    return content

def remove_collection_add(content, plugin_class_name):
    """Remove collection->add() call with optional second parameter."""
    pattern = rf'\s*\$\w+->add\(\s*new\s+{re.escape(plugin_class_name)}\s*\([^)]*\)\s*(?:,\s*[^;]+)?\s*\);\s*'
    content = re.sub(pattern, '', content, flags=re.DOTALL)
    return content

def remove_resource_relationship(content, resource_type, plugin_class_name):
    """Remove addRelationship() call for a specific resource type and plugin."""
    pattern = rf'\s*\$\w+->addRelationship\(\s*{re.escape(resource_type)}\s*,\s*new\s+{re.escape(plugin_class_name)}\s*\([^)]*\)\s*,?\s*\);\s*'
    content = re.sub(pattern, '', content, flags=re.DOTALL)
    return content

def remove_method(content, method_name):
    """Remove method with its docblock."""
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
    pattern = rf'\s*{re.escape(queue_config_key)}\s*=>\s*new\s+\w+\([^)]*\),\s*'
    content = re.sub(pattern, '', content)
    return content

def remove_array_constant_entry(content, constant_reference):
    """Remove array entry that is a config constant reference."""
    pattern = rf'\s*{re.escape(constant_reference)}\s*,\s*'
    content = re.sub(pattern, '', content)
    return content

def remove_data_import_console(content, config_constant):
    """Remove DataImportConsole instantiation with specific config constant."""
    pattern = rf'\s*new\s+DataImportConsole\(DataImportConsole::DEFAULT_NAME\s*\.\s*static::COMMAND_SEPARATOR\s*\.\s*{re.escape(config_constant)}\),?\s*'
    content = re.sub(pattern, '', content, flags=re.MULTILINE)
    return content

def remove_array_value_entry(content, array_value):
    """Remove array value entry from arrays."""
    pattern = rf'\s*{re.escape(array_value)},?\s*\n'
    content = re.sub(pattern, '', content)
    return content

def remove_config_assignment(content, constant_pattern):
    """Remove config assignment line (including multi-line assignments)."""
    pattern = rf'^\s*\$config\[{re.escape(constant_pattern)}[^\]]*\]\s*=.*?;\s*$'
    content = re.sub(pattern, '', content, flags=re.MULTILINE | re.DOTALL)
    return content

def remove_array_value(content, array_value):
    """Remove array value from arrays (like dependency injector arrays)."""
    pattern = rf"\s*'{re.escape(array_value)}',\s*"
    content = re.sub(pattern, '', content)
    return content

def remove_filesystem_config(content, filesystem_name):
    """Remove filesystem configuration block."""
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
    pattern = rf'\s*{re.escape(key_pattern)}\s*=>\s*[^,]+,\s*'
    content = re.sub(pattern, '', content, flags=re.MULTILINE | re.DOTALL)
    return content

def cleanup_content(content):
    """Clean up multiple empty lines and ensure single newline at end."""
    content = re.sub(r'\n{3,}', '\n\n', content)
    content = content.rstrip() + '\n'
    return content

def process_file(config):
    """Process file based on configuration."""
    file_path = config['file_path']

    try:
        content = read_file(file_path)
    except FileNotFoundError:
        print(f"⚠ File not found: {file_path}")
        return

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
        elif op_type == 'remove_array_value_entry':
            content = remove_array_value_entry(content, operation['array_value'])
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
    process_file(config)
PYTHON_SCRIPT

echo "Step 3: Processing PHP dependency provider files from configuration..."
python3 << 'PYTHON_PROCESS'
import json

# Load configuration
with open('uninstall-marketplace-config.json', 'r') as f:
    config = json.load(f)

# Process all PHP dependency providers
import subprocess
step = 3
for file_path, file_config in config['php_dependency_providers'].items():
    full_config = {
        'file_path': file_path,
        'operations': file_config['operations'],
        'success_messages': file_config['success_messages']
    }
    
    config_json = json.dumps(full_config)
    
    try:
        subprocess.run(
            ['python3', '/tmp/marketplace_cleanup.py', config_json],
            check=False
        )
    except Exception as e:
        print(f"⚠ Error processing {file_path}: {e}")

print(f"✓ Processed {len(config['php_dependency_providers'])} PHP files")
PYTHON_PROCESS

echo ""

echo "Step 4: Processing configuration files..."
python3 << 'PYTHON_PROCESS'
import json
import subprocess

# Load configuration
with open('uninstall-marketplace-config.json', 'r') as f:
    config = json.load(f)

# Process all config files
for file_path, file_config in config['config_files'].items():
    full_config = {
        'file_path': file_path,
        'operations': file_config['operations'],
        'success_messages': file_config['success_messages']
    }
    
    config_json = json.dumps(full_config)
    
    try:
        subprocess.run(
            ['python3', '/tmp/marketplace_cleanup.py', config_json],
            check=False
        )
    except Exception as e:
        print(f"⚠ Error processing {file_path}: {e}")

print(f"✓ Processed {len(config['config_files'])} configuration files")
PYTHON_PROCESS

echo ""

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

echo "Step 5: Removing marketplace directories..."
python3 << 'PYTHON_PROCESS' | while IFS=':' read -r dir_path dir_name; do
import json

# Load configuration
with open('uninstall-marketplace-config.json', 'r') as f:
    config = json.load(f)

# Output directories for bash to process
for dir_entry in config['directories_to_remove']:
    print(dir_entry)
PYTHON_PROCESS
    remove_directory "$dir_path" "$dir_name"
done
echo ""

echo "Step 6: Removing merchant data import entities from import configuration files..."
cat > /tmp/remove_import_entities.py << 'PYTHON_SCRIPT'
import sys
import re

def remove_data_import_entities(content, entities):
    """Remove specific data_entity blocks from import YAML files."""
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

        # Check if this line starts a data_entity block we want to remove
        should_remove = False

        for entity in entities:
            # Match: - data_entity: entity-name
            if re.match(rf'-\s+data_entity:\s+{re.escape(entity)}\s*$', stripped):
                should_remove = True
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

def process_import_yaml(file_path, entities):
    """Process import YAML file to remove specified data entities."""
    try:
        with open(file_path, 'r') as f:
            content = f.read()

        # Check if any entities exist
        found = False
        for entity in entities:
            if f'data_entity: {entity}' in content:
                found = True
                break

        if not found:
            return False

        # Remove entities
        new_content = remove_data_import_entities(content, entities)

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
    entities = sys.argv[2].split(',')
    result = process_import_yaml(file_path, entities)
    if result is True:
        print(f"✓ Removed merchant data import entities from {file_path}")
    elif result is False:
        print(f"⚠ No merchant data import entities found in {file_path}")
    else:
        print(f"⚠ File not found: {file_path}")
PYTHON_SCRIPT

python3 << 'PYTHON_PROCESS' | {
import json

# Load configuration
with open('uninstall-marketplace-config.json', 'r') as f:
    config = json.load(f)

# Output data for processing
for import_file in config['import_yaml_files']:
    print(import_file)
    
entities = ','.join(config['import_entities_to_remove'])
print(f"ENTITIES:{entities}")
PYTHON_PROCESS
    import_files=()
    while IFS= read -r line; do
        if [[ $line == ENTITIES:* ]]; then
            ENTITIES_TO_REMOVE="${line#ENTITIES:}"
        else
            import_files+=("$line")
        fi
    done
    
    for import_file in "${import_files[@]}"; do
        if [ -f "$import_file" ]; then
            python3 /tmp/remove_import_entities.py "$import_file" "$ENTITIES_TO_REMOVE"
        else
            echo "⚠ Import file not found: $import_file"
        fi
    done
}
echo ""

echo "Step 7: Removing merchant-portal commands from docker configuration files..."
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

python3 << 'PYTHON_PROCESS' | while IFS='|' read -r docker_file commands; do
import json

# Load configuration
with open('uninstall-marketplace-config.json', 'r') as f:
    config = json.load(f)

# Process docker config files
commands = ','.join(config['docker_commands_to_remove'])
for docker_file in config['docker_config_files']:
    print(f"{docker_file}|{commands}")
PYTHON_PROCESS
    if [ -f "$docker_file" ]; then
        python3 /tmp/remove_docker_commands.py "$docker_file" "$commands"
    else
        echo "⚠ Docker config file not found: $docker_file"
    fi
done
echo ""

echo "Step 8: Updating data import command in docker.yml..."
DOCKER_YML_FILE="config/install/docker.yml"
if [ -f "$DOCKER_YML_FILE" ]; then
    sed -i.bak "s/data:import --config=data\/import\/local\/full_\${SPRYKER_REGION}\.yml/data:import --config=data\/import\/local\/b2b_full_EU.yml/g" "$DOCKER_YML_FILE"
    rm -f "${DOCKER_YML_FILE}.bak"
    echo "✓ Updated data import command to use b2b_full_EU.yml"
else
    echo "⚠ docker.yml file not found at $DOCKER_YML_FILE"
fi
echo ""

echo "Step 9: Removing merchant-portal applications from deploy files..."
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

python3 << 'PYTHON_PROCESS' | while IFS= read -r deploy_file; do
import json

# Load configuration
with open('uninstall-marketplace-config.json', 'r') as f:
    config = json.load(f)

for deploy_file in config['deploy_files']:
    print(deploy_file)
PYTHON_PROCESS
    if [ -f "$deploy_file" ]; then
        python3 /tmp/remove_merchant_portal_apps.py "$deploy_file"
    else
        echo "⚠ Deploy file not found: $deploy_file"
    fi
done
echo ""

echo "Step 10: Removing marketplace-specific OMS subprocesses..."
OMS_XML_FILE="config/Zed/oms/DummyPayment01.xml"
if [ -f "$OMS_XML_FILE" ]; then
    # Remove DummyMerchantCommission subprocess reference and definition
    sed -i.bak '/<process>DummyMerchantCommission<\/process>/d' "$OMS_XML_FILE"
    sed -i.bak '/<process name="DummyMerchantCommission" file="DummySubprocess\/DummyMerchantCommission01.xml"\/>/d' "$OMS_XML_FILE"
    rm -f "${OMS_XML_FILE}.bak"
    echo "✓ Removed DummyMerchantCommission subprocess from DummyPayment01.xml"
else
    echo "⚠ OMS file not found at $OMS_XML_FILE"
fi
echo ""

echo "Step 11: Removing MerchantCommissionHelper from codeception config..."
CODECEPTION_DYNAMIC_FIXTURES_FILE="tests/PyzTest/Zed/TestifyBackendApi/codeception.dynamic.fixtures.yml"
if [ -f "$CODECEPTION_DYNAMIC_FIXTURES_FILE" ]; then
    sed -i.bak '/\\SprykerTest\\Shared\\MerchantCommission\\Helper\\MerchantCommissionHelper/d' "$CODECEPTION_DYNAMIC_FIXTURES_FILE"
    rm -f "${CODECEPTION_DYNAMIC_FIXTURES_FILE}.bak"
    echo "✓ Removed MerchantCommissionHelper from codeception.dynamic.fixtures.yml"
else
    echo "⚠ Codeception config file not found at $CODECEPTION_DYNAMIC_FIXTURES_FILE"
fi
echo ""

echo "Step 12: Running composer update to apply all changes..."
composer update --ignore-platform-req=ext-grpc --ignore-platform-req=ext-redis
echo "✓ Composer update completed"
echo ""

echo "=========================================="
echo "Marketplace packages uninstalled successfully!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Review and test the changes"
echo "2. Run 'composer install' to ensure consistency"
echo "3. Clear caches and rebuild: docker/sdk boot && docker/sdk up --build"
echo "4. Verify the application works without marketplace features"
echo ""
echo "Configuration file used: $CONFIG_FILE"
echo "This file can be modified to adjust cleanup behavior"
