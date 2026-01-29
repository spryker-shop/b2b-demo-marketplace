#!/bin/bash

# Script to uninstall marketplace-specific frontend files from B2B Marketplace
# This will remove all marketplace frontend features and related files

set -e

echo "=========================================="
echo "Uninstalling Frontend Marketplace Files"
echo "=========================================="
echo ""

# Files to remove
FRONTEND_FILES=(
    "tsconfig.mp.json"
    ".stylelintrc.mp.js"
    "angular.json"
)

# Directories to remove
FRONTEND_DIRECTORIES=(
    "frontend/merchant-portal"
)

echo "Step 1: Removing frontend configuration files..."
for file in "${FRONTEND_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "  Removing $file..."
        rm -f "$file"
        echo "  ✓ Removed $file"
    else
        echo "  ⚠ File not found: $file (skipping)"
    fi
done
echo "✓ Frontend configuration files removed"
echo ""

echo "Step 2: Removing frontend directories..."
for dir in "${FRONTEND_DIRECTORIES[@]}"; do
    if [ -d "$dir" ]; then
        echo "  Removing $dir..."
        rm -rf "$dir"
        echo "  ✓ Removed $dir"
    else
        echo "  ⚠ Directory not found: $dir (skipping)"
    fi
done
echo "✓ Frontend directories removed"
echo ""

echo "Step 3: Cleaning up eslint.config.mjs..."
if [ -f "eslint.config.mjs" ]; then
    # Create a Python script to remove Merchant Portal configurations
    cat > /tmp/cleanup_eslint.py << 'PYTHON_SCRIPT'
import sys
import re

def cleanup_eslint_config(file_path):
    """Remove Merchant Portal configuration blocks from eslint.config.mjs"""
    with open(file_path, 'r') as f:
        lines = f.readlines()

    output_lines = []
    skip_block = False
    brace_count = 0
    in_mp_block = False

    i = 0
    while i < len(lines):
        line = lines[i]

        # Remove angular-eslint import
        if "import angularEslint from 'angular-eslint';" in line:
            i += 1
            continue

        # Check if we're entering a Merchant Portal configuration block
        if '// Configuration for Merchant Portal TypeScript files' in line or \
           '// Configuration for Merchant Portal HTML templates' in line:
            skip_block = True
            in_mp_block = True
            brace_count = 0
            i += 1
            continue

        if skip_block:
            # Count braces to know when the configuration block ends
            brace_count += line.count('{') - line.count('}')

            # If we've closed all braces, the block is done
            if in_mp_block and brace_count == 0 and '},' in line:
                skip_block = False
                in_mp_block = False
                i += 1
                continue

        if not skip_block:
            output_lines.append(line)

        i += 1

    # Write the cleaned content back
    with open(file_path, 'w') as f:
        f.writelines(output_lines)

    print("  ✓ Removed Merchant Portal configurations from eslint.config.mjs")

if __name__ == '__main__':
    cleanup_eslint_config('eslint.config.mjs')
PYTHON_SCRIPT

    python3 /tmp/cleanup_eslint.py
    rm /tmp/cleanup_eslint.py
else
    echo "  ⚠ eslint.config.mjs not found (skipping)"
fi
echo "✓ ESLint configuration cleaned"
echo ""

echo "Step 4: Cleaning up package.json..."
if [ -f "package.json" ]; then
    # Create a Python script to remove Merchant Portal scripts and dependencies
    cat > /tmp/cleanup_package_json.py << 'PYTHON_SCRIPT'
import json
import re

def cleanup_package_json(file_path):
    """Remove Merchant Portal scripts and dependencies from package.json"""
    with open(file_path, 'r') as f:
        package_data = json.load(f)

    # Remove mp:* scripts
    scripts_removed = 0
    if 'scripts' in package_data:
        scripts_to_remove = [key for key in package_data['scripts'].keys() if key.startswith('mp:')]
        for script in scripts_to_remove:
            del package_data['scripts'][script]
            scripts_removed += 1

        # Also remove postinstall if it references mp:update:paths
        if 'postinstall' in package_data['scripts'] and 'mp:update:paths' in package_data['scripts']['postinstall']:
            del package_data['scripts']['postinstall']
            scripts_removed += 1

    # Define packages to remove
    packages_to_remove = [
        # Angular packages
        '@angular/animations',
        '@angular/cdk',
        '@angular/common',
        '@angular/compiler',
        '@angular/core',
        '@angular/elements',
        '@angular/forms',
        '@angular/platform-browser',
        '@angular/platform-browser-dynamic',
        '@angular/router',
        '@angular-builders/custom-webpack',
        '@angular-builders/jest',
        '@angular-devkit/build-angular',
        '@angular-eslint/builder',
        '@angular-eslint/eslint-plugin',
        '@angular-eslint/eslint-plugin-template',
        '@angular-eslint/template-parser',
        '@angular/cli',
        '@angular/compiler-cli',
        '@angular/language-service',
        'angular-eslint',
        # Other merchant portal dependencies
        'ng-zorro-antd',
        'zone.js',
        'rxjs',
        '@ctrl/tinycolor',
        # Jest packages
        'jest',
        '@types/jest',
        'jest-environment-jsdom',
        'jest-preset-angular',
    ]

    # Remove from dependencies
    deps_removed = 0
    if 'dependencies' in package_data:
        for package in packages_to_remove:
            if package in package_data['dependencies']:
                del package_data['dependencies'][package]
                deps_removed += 1

    # Remove from devDependencies
    dev_deps_removed = 0
    if 'devDependencies' in package_data:
        for package in packages_to_remove:
            if package in package_data['devDependencies']:
                del package_data['devDependencies'][package]
                dev_deps_removed += 1

    # Write back to file
    with open(file_path, 'w') as f:
        json.dump(package_data, f, indent=4)
        f.write('\n')

    print(f"  ✓ Removed {scripts_removed} mp:* scripts")
    print(f"  ✓ Removed {deps_removed} dependencies")
    print(f"  ✓ Removed {dev_deps_removed} devDependencies")

if __name__ == '__main__':
    cleanup_package_json('package.json')
PYTHON_SCRIPT

    python3 /tmp/cleanup_package_json.py
    rm /tmp/cleanup_package_json.py
else
    echo "  ⚠ package.json not found (skipping)"
fi
echo "✓ package.json cleaned"
echo ""

echo "Step 5: Updating npm dependencies..."
npm install
echo "✓ Dependencies updated"
echo ""

echo "Step 6: Installing @babel/core..."
npm install --save-dev @babel/core
echo "✓ @babel/core installed"
echo ""

echo "Step 7: Cleaning npm cache..."
rm -rf node_modules/.cache
echo "✓ Cache cleaned"
echo ""

echo "=========================================="
echo "Frontend Marketplace Uninstallation Complete"
echo "=========================================="
echo ""
echo "Summary:"
echo "  - Removed ${#FRONTEND_FILES[@]} configuration files"
echo "  - Removed ${#FRONTEND_DIRECTORIES[@]} directories"
echo "  - Cleaned Merchant Portal blocks from eslint.config.mjs"
echo "  - Cleaned Merchant Portal scripts and dependencies from package.json"
echo "  - Updated npm dependencies"
echo "  - Installed @babel/core"
echo "  - Cleaned npm cache"
echo ""
echo "Next steps:"
echo "  1. Review changes"
echo "  2. Make sure composer dependencies are installed (run 'composer install --ignore-platform-req=ext-grpc' if needed)"
echo ""
