<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\Security;

use Spryker\Zed\AgentSecurityMerchantPortalGui\Communication\Plugin\MultiFactorAuth\MultiFactorAuthenticationAgentMerchantUserSecurityPlugin;
use Spryker\Zed\AgentSecurityMerchantPortalGui\Communication\Plugin\Security\ZedAgentSecurityPlugin;
use Spryker\Zed\AgentSecurityMerchantPortalGui\Communication\Plugin\Security\ZedMerchantUserSecurityPlugin as AgentZedMerchantUserSecurityPlugin;
use Spryker\Zed\Security\SecurityDependencyProvider as SprykerSecurityDependencyProvider;
use Spryker\Zed\SecurityGui\Communication\Plugin\Security\ZedUserSecurityPlugin;
use Spryker\Zed\SecurityMerchantPortalGui\Communication\Plugin\MultiFactorAuth\MultiFactorAuthenticationMerchantUserSecurityPlugin;
use Spryker\Zed\SecurityMerchantPortalGui\Communication\Plugin\Security\ZedMerchantUserSecurityPlugin;
use Spryker\Zed\SecurityMerchantPortalGui\Communication\Plugin\Security\ZedOauthMerchantPortalSecurityPlugin;
use Spryker\Zed\SecurityOauthKnpu\Communication\Plugin\Security\ZedKnpuOauthUserSecurityPlugin;
use Spryker\Zed\SecurityOauthUser\Communication\Plugin\Security\ZedOauthUserSecurityPlugin;
use Spryker\Zed\SecuritySystemUser\Communication\Plugin\Security\ZedSystemUserSecurityPlugin;
use Spryker\Zed\SessionUserValidation\Communication\Plugin\Security\SaveSessionUserSecurityPlugin;
use Spryker\Zed\SessionUserValidation\Communication\Plugin\Security\ZedValidateSessionUserSecurityPlugin;
use Spryker\Zed\User\Communication\Plugin\Security\ZedUserSessionHandlerSecurityPlugin;

class SecurityDependencyProvider extends SprykerSecurityDependencyProvider
{
    /**
     * @return array<\Spryker\Shared\SecurityExtension\Dependency\Plugin\SecurityPluginInterface>
     */
    protected function getSecurityPlugins(): array
    {
        // `agent-security-merchant-portal-gui` and `security-oauth-knpu` are removed on the b2b (non-marketplace)
        // variant. The agent firewall (narrower pattern) MUST be registered before the base merchant-user firewall
        // (broader pattern) — Symfony's firewall map matches in registration order, first pattern wins.
        $securityPlugins = [
            new ZedUserSessionHandlerSecurityPlugin(),
            new ZedSystemUserSecurityPlugin(),
        ];

        if (class_exists(ZedAgentSecurityPlugin::class)) {
            $securityPlugins[] = new ZedAgentSecurityPlugin();
        }

        if (class_exists(AgentZedMerchantUserSecurityPlugin::class)) {
            $securityPlugins[] = new AgentZedMerchantUserSecurityPlugin();
        }

        $securityPlugins[] = new ZedMerchantUserSecurityPlugin();
        $securityPlugins[] = new ZedOauthMerchantPortalSecurityPlugin();
        $securityPlugins[] = new MultiFactorAuthenticationMerchantUserSecurityPlugin();

        if (class_exists(MultiFactorAuthenticationAgentMerchantUserSecurityPlugin::class)) {
            $securityPlugins[] = new MultiFactorAuthenticationAgentMerchantUserSecurityPlugin();
        }

        if (class_exists(ZedKnpuOauthUserSecurityPlugin::class)) {
            $securityPlugins[] = new ZedKnpuOauthUserSecurityPlugin();
        }

        $securityPlugins[] = new ZedUserSecurityPlugin();
        $securityPlugins[] = new ZedOauthUserSecurityPlugin();
        $securityPlugins[] = new ZedValidateSessionUserSecurityPlugin();
        $securityPlugins[] = new SaveSessionUserSecurityPlugin();

        return $securityPlugins;
    }
}
