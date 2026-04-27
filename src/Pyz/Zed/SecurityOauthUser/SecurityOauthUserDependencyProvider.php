<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SecurityOauthUser;

use Spryker\Zed\SecurityOauthKnpu\Communication\Plugin\SecurityOauthUser\KnpuOauthUserClientStrategyPlugin;
use Spryker\Zed\SecurityOauthKnpu\Communication\Plugin\SecurityOauthUser\KnpuOauthUserIdentityPersistencePlugin;
use Spryker\Zed\SecurityOauthKnpu\Communication\Plugin\SecurityOauthUser\KnpuOauthUserIdentityStrategyPlugin;
use Spryker\Zed\SecurityOauthUser\SecurityOauthUserDependencyProvider as SprykerSecurityOauthUserDependencyProvider;

class SecurityOauthUserDependencyProvider extends SprykerSecurityOauthUserDependencyProvider
{
    /**
     * @return array<\Spryker\Zed\SecurityOauthUserExtension\Dependency\Plugin\OauthUserClientStrategyPluginInterface>
     */
    protected function getOauthUserClientStrategyPlugins(): array
    {
        return [
            new KnpuOauthUserClientStrategyPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\SecurityOauthUserExtension\Dependency\Plugin\OauthUserAuthenticationStrategyPluginInterface>
     */
    protected function getOauthUserAuthenticationStrategyPlugins(): array
    {
        return [
            new KnpuOauthUserIdentityStrategyPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\SecurityOauthUserExtension\Dependency\Plugin\OauthUserPostResolvePluginInterface>
     */
    protected function getOauthUserPostResolvePlugins(): array
    {
        return [
            new KnpuOauthUserIdentityPersistencePlugin(),
        ];
    }
}