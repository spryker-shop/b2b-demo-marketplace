<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\Acl;

use Pyz\Zed\TenantOnboarding\TenantOnboardingConfig;
use Spryker\Shared\Acl\AclConstants;
use Spryker\Zed\Acl\AclConfig as SprykerAclConfig;

class AclConfig extends SprykerAclConfig
{
    /**
     * @var string
     */
    protected const RULE_TYPE_DENY = 'deny';

    /**
     * @return array<array<string, mixed>>
     */
    public function getInstallerUsers(): array
    {
        return [
            'admin@spryker.com' => [
                'group' => AclConstants::ROOT_GROUP,
            ],
            'admin_de@spryker.com' => [
                'group' => AclConstants::ROOT_GROUP,
            ],
            'richard@spryker.com' => [
                'group' => AclConstants::ROOT_GROUP,
            ],
            'agent-merchant@spryker.com' => [
                'group' => AclConstants::ROOT_GROUP,
            ],
        ];
    }

    /**
     * @param array<array<string, mixed>> $installerRules
     *
     * @return array<array<string, mixed>>
     */
    protected function addMerchantPortalInstallerRules(array $installerRules): array
    {
        $bundleNames = [
            'dashboard-merchant-portal-gui',
            'merchant-profile-merchant-portal-gui',
            'product-merchant-portal-gui',
            'product-offer-merchant-portal-gui',
            'security-merchant-portal-gui',
            'sales-merchant-portal-gui',
            'user-merchant-portal-gui',
            'agent-dashboard-merchant-portal-gui',
            'merchant-relation-request-merchant-portal-gui',
            'merchant-relationship-merchant-portal-gui',
            'merchant-app-merchant-portal-gui',
        ];

        foreach ($bundleNames as $bundleName) {
            $installerRules[] = [
                'bundle' => $bundleName,
                'controller' => AclConstants::VALIDATOR_WILDCARD,
                'action' => AclConstants::VALIDATOR_WILDCARD,
                'type' => static::RULE_TYPE_DENY,
                'role' => AclConstants::ROOT_ROLE,
            ];
        }

        return $installerRules;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getInstallerRules(): array
    {
        $installerRules = parent::getInstallerRules();
        $installerRules = $this->addMerchantPortalInstallerRules($installerRules);
        $installerRules = $this->addTenantManagerInstallerRules($installerRules);

        return $installerRules;
    }

    public function getInstallerRoles(): array
    {
        $installerRoles = parent::getInstallerRoles();
        $installerRoles[] = [
            'name' => TenantOnboardingConfig::ROLE_TENANT_MANAGER,
            'group' => TenantOnboardingConfig::GROUP_TENANT_MANAGER,
        ];
        $installerRoles[] = [
            'name' => AclConstants::ROOT_ROLE,
            'group' => TenantOnboardingConfig::GROUP_TENANT_MANAGER,
        ];

        return $installerRoles;
    }

    public function getInstallerGroups(): array
    {
        $installerGroups = parent::getInstallerGroups();
        $installerGroups[] = [
            'name' => TenantOnboardingConfig::GROUP_TENANT_MANAGER,
            'description' => 'Root group for the Tenant Manager',
        ];

        return $installerGroups;
    }

    protected function addTenantManagerInstallerRules(array $installerRules): array
    {
        $bundleNames = [
            'user',
            'acl',
            'storage-gui',
            'spryk-gui',
            'queue',
            'search-elasticsearch-gui',
            'development',
            'maintenance',
            'permission',
            'tenant-onboarding',
            'tenant-assigner',
        ];

        foreach ($bundleNames as $bundleName) {
            $installerRules[] = [
                'bundle' => $bundleName,
                'controller' => AclConstants::VALIDATOR_WILDCARD,
                'action' => AclConstants::VALIDATOR_WILDCARD,
                'type' => static::RULE_TYPE_DENY,
                'role' => TenantOnboardingConfig::ROLE_TENANT_MANAGER,
            ];
        }

        return $installerRules;
    }
}
