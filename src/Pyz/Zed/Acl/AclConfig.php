<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\Acl;

use Generated\Shared\Transfer\GroupTransfer;
use Spryker\Shared\Acl\AclConstants;
use Spryker\Zed\Acl\AclConfig as SprykerAclConfig;

class AclConfig extends SprykerAclConfig
{
    /**
     * @var string
     */
    protected const RULE_TYPE_DENY = 'deny';

    /**
     * @var string
     */
    protected const ROLE_DISCOUNT_MANAGER = 'Discount Manager';

    /**
     * @var string
     */
    protected const GROUP_DISCOUNT_MANAGERS = 'Discount Managers';

    /**
     * @var string
     */
    protected const GROUP_REFERENCE_DISCOUNT_MANAGERS = 'discount-managers-group';

    /**
     * @uses \Spryker\Zed\SecurityMerchantPortalGui\Communication\Builder\OptionsBuilder::PATH_LOGOUT
     */
    protected const string MERCHANT_PORTAL_ACCESS_DENIED_URI = '/security-merchant-portal-gui/logout';

    protected const string APPLICATION_MERCHANT_PORTAL = 'MERCHANT_PORTAL';

    public function getAccessDeniedUri(): string
    {
        if (APPLICATION === static::APPLICATION_MERCHANT_PORTAL) {
            return static::MERCHANT_PORTAL_ACCESS_DENIED_URI;
        }

        return parent::getAccessDeniedUri();
    }

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
            'diego@spryker.com' => [
                'group' => static::GROUP_DISCOUNT_MANAGERS,
            ],
        ];
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getInstallerGroups(): array
    {
        $installerGroups = parent::getInstallerGroups();

        $installerGroups[] = [
            GroupTransfer::NAME => static::GROUP_DISCOUNT_MANAGERS,
            GroupTransfer::REFERENCE => static::GROUP_REFERENCE_DISCOUNT_MANAGERS,
        ];

        return $installerGroups;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getInstallerRoles(): array
    {
        $installerRoles = parent::getInstallerRoles();

        $installerRoles[] = [
            'name' => static::ROLE_DISCOUNT_MANAGER,
            'group' => static::GROUP_DISCOUNT_MANAGERS,
        ];

        return $installerRoles;
    }

    /**
     * @param array<array<string, mixed>> $installerRules
     *
     * @return array<array<string, mixed>>
     */
    protected function addDiscountManagerInstallerRules(array $installerRules): array
    {
        $discountManagerRules = [
            [
                'bundle' => 'ai-commerce',
                'controller' => AclConstants::VALIDATOR_WILDCARD,
                'action' => AclConstants::VALIDATOR_WILDCARD,
            ],
            [
                'bundle' => 'discount',
                'controller' => 'index',
                'action' => 'list',
            ],
            [
                'bundle' => 'discount',
                'controller' => AclConstants::VALIDATOR_WILDCARD,
                'action' => AclConstants::VALIDATOR_WILDCARD,
            ],
            [
                'bundle' => 'dashboard',
                'controller' => AclConstants::VALIDATOR_WILDCARD,
                'action' => AclConstants::VALIDATOR_WILDCARD,
            ],
        ];

        foreach ($discountManagerRules as $discountManagerRule) {
            $installerRules[] = $discountManagerRule + [
                'type' => AclConstants::ALLOW,
                'role' => static::ROLE_DISCOUNT_MANAGER,
            ];
        }

        return $installerRules;
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
            'data-import-merchant-portal-gui',
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
        $installerRules = $this->addDiscountManagerInstallerRules($installerRules);

        return $installerRules;
    }
}
