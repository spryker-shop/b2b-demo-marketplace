<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SecurityMerchantPortalGui;

use Spryker\Zed\AclMerchantPortal\Communication\Plugin\SecurityMerchantPortalGui\AclGroupMerchantUserLoginRestrictionPlugin;
use Spryker\Zed\AgentSecurityMerchantPortalGui\Communication\Plugin\SecurityMerchantPortalGui\AgentMerchantUserCriteriaExpanderPlugin;
use Spryker\Zed\MultiFactorAuthMerchantPortal\Communication\Plugin\AuthenticationHandler\MerchantUser\MerchantUserMultiFactorAuthenticationHandlerPlugin;
use Spryker\Zed\SecurityMerchantPortalGui\Communication\Plugin\Security\Handler\LastVisitedPageMerchantPortalUserRedirectStrategyPlugin;
use Spryker\Zed\SecurityMerchantPortalGui\Communication\Plugin\SecurityMerchantPortalGui\ExistingMerchantUserAuthenticationStrategyPlugin;
use Spryker\Zed\SecurityMerchantPortalGui\SecurityMerchantPortalGuiDependencyProvider as SprykerSecurityMerchantPortalGuiDependencyProvider;
use Spryker\Zed\SecurityOauthKnpu\Communication\Plugin\SecurityMerchantPortalGui\KnpuOauthMerchantUserAuthenticationLinkPlugin;
use Spryker\Zed\SecurityOauthKnpu\Communication\Plugin\SecurityOauthMerchantPortal\KnpuOauthMerchantUserClientStrategyPlugin;
use Spryker\Zed\SecurityOauthKnpu\Communication\Plugin\SecurityOauthMerchantPortal\KnpuOauthMerchantUserIdentityPersistencePlugin;
use Spryker\Zed\SecurityOauthKnpu\Communication\Plugin\SecurityOauthMerchantPortal\KnpuOauthMerchantUserIdentityStrategyPlugin;

class SecurityMerchantPortalGuiDependencyProvider extends SprykerSecurityMerchantPortalGuiDependencyProvider
{
    /**
     * @return array<\Spryker\Zed\SecurityMerchantPortalGuiExtension\Dependency\Plugin\MerchantUserLoginRestrictionPluginInterface>
     */
    protected function getMerchantUserLoginRestrictionPlugins(): array
    {
        return [
            new AclGroupMerchantUserLoginRestrictionPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\SecurityMerchantPortalGuiExtension\Dependency\Plugin\MerchantUserCriteriaExpanderPluginInterface>
     */
    protected function getMerchantUserCriteriaExpanderPlugins(): array
    {
        return [
            new AgentMerchantUserCriteriaExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\SecurityMerchantPortalGuiExtension\Dependency\Plugin\AuthenticationHandlerPluginInterface>
     */
    protected function getMerchantUserAuthenticationHandlerPlugins(): array
    {
        return [
            new MerchantUserMultiFactorAuthenticationHandlerPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\SecurityMerchantPortalGuiExtension\Dependency\Plugin\MerchantPortalUserRedirectStrategyPluginInterface>
     */
    protected function getMerchantPortalUserRedirectStrategyPlugins(): array
    {
        return [
            new LastVisitedPageMerchantPortalUserRedirectStrategyPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\SecurityMerchantPortalGuiExtension\Dependency\Plugin\MerchantUserAuthenticationLinkPluginInterface>
     */
    protected function getMerchantPortalAuthenticationLinkPlugins(): array
    {
        return [
            new KnpuOauthMerchantUserAuthenticationLinkPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\SecurityMerchantPortalGuiExtension\Dependency\Plugin\OauthMerchantUserClientStrategyPluginInterface>
     */
    protected function getOauthMerchantUserClientStrategyPlugins(): array
    {
        return [
            new KnpuOauthMerchantUserClientStrategyPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\SecurityMerchantPortalGuiExtension\Dependency\Plugin\OauthMerchantUserAuthenticationStrategyPluginInterface>
     */
    protected function getOauthMerchantUserAuthenticationStrategyPlugins(): array
    {
        return [
            // Knpu identity lookup first (fast path for returning users)
            new KnpuOauthMerchantUserIdentityStrategyPlugin(),
            // Email-based lookup last (first-login path for pre-existing merchant users)
            new ExistingMerchantUserAuthenticationStrategyPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\SecurityMerchantPortalGuiExtension\Dependency\Plugin\OauthMerchantUserPostResolvePluginInterface>
     */
    protected function getOauthMerchantUserPostResolvePlugins(): array
    {
        return [
            new KnpuOauthMerchantUserIdentityPersistencePlugin(),
        ];
    }
}
