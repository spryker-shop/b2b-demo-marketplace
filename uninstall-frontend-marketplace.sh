#!/bin/bash

# Script to uninstall marketplace-specific frontend files from B2B Marketplace
#
# The Merchant Portal build tooling now ships inside spryker/zed-ui rather than living in the
# project, so this script only has to undo what the project itself still declares: the npm scripts
# that drive the mp-zed-ui workspace. Everything else — the Angular toolchain, the lint and
# TypeScript configurations, the builder itself — disappears with the module when
# uninstall-marketplace-modules.sh removes it via composer.

set -e

echo "=========================================="
echo "Uninstalling Frontend Marketplace Files"
echo "=========================================="
echo ""

echo "Cleaning up package.json..."
if [ -f "package.json" ]; then
    cat > /tmp/cleanup_package_json.py << 'PYTHON_SCRIPT'
import json


def cleanup_package_json(file_path):
    """Remove Merchant Portal scripts from package.json"""
    with open(file_path, 'r') as f:
        package_data = json.load(f)

    scripts_removed = 0
    if 'scripts' in package_data:
        scripts_to_remove = [key for key in package_data['scripts'].keys() if key.startswith('mp:')]
        for script in scripts_to_remove:
            del package_data['scripts'][script]
            scripts_removed += 1

        # Also strip the Merchant Portal steps from postinstall. The project owned that tooling
        # before ("mp:update:paths"); it now delegates to the mp-zed-ui workspace shipped by
        # spryker/zed-ui, which uninstall-marketplace-modules.sh removes right after this script.
        # Leaving those steps behind makes every later "npm ci" fail on a workspace that is gone.
        # The remaining steps (update:config for the Zed and Yves builders) must stay: they
        # generate the tsconfig files the Zed and Yves builds read.
        postinstall_markers = ('mp:update:paths', 'mp-zed-ui')
        postinstall = package_data['scripts'].get('postinstall', '')
        steps = [step.strip() for step in postinstall.split('&&') if step.strip()]
        kept_steps = [step for step in steps if not any(marker in step for marker in postinstall_markers)]
        if len(kept_steps) != len(steps):
            if kept_steps:
                package_data['scripts']['postinstall'] = ' && '.join(kept_steps)
            else:
                del package_data['scripts']['postinstall']
            scripts_removed += 1

    with open(file_path, 'w') as f:
        json.dump(package_data, f, indent=4)
        f.write('\n')

    print(f"  ✓ Removed {scripts_removed} Merchant Portal scripts")


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

echo "=========================================="
echo "Frontend Marketplace Uninstallation Complete"
echo "=========================================="
echo ""
echo "Next steps:"
echo "  1. Review changes"
echo "  2. Run uninstall-marketplace-modules.sh to remove the marketplace packages via composer"
echo ""
