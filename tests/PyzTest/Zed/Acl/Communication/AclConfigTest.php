<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\Acl\Communication;

use Codeception\Test\Unit;
use Pyz\Zed\Acl\AclConfig;
use Spryker\Shared\Acl\AclConstants;
use Spryker\Shared\Config\Config;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Zed
 * @group Acl
 * @group Communication
 * @group AclConfigTest
 * Add your own group annotations below this line
 */
class AclConfigTest extends Unit
{
    public function testAclModuleConfigKeysAreNotDefinedInTheEnvironmentConfig(): void
    {
        // Arrange
        $aclConfigKeys = [
            AclConstants::ACL_DEFAULT_RULES,
            AclConstants::ACL_DEFAULT_CREDENTIALS,
            AclConstants::ACL_USER_RULE_WHITELIST,
        ];

        // Act
        $definedAclConfigKeys = array_filter($aclConfigKeys, Config::hasKey(...));

        // Assert
        $this->assertSame([], $definedAclConfigKeys);
    }

    public function testGetRulesIncludesTheMerchantPortalDefaultRule(): void
    {
        // Arrange
        $aclConfig = new AclConfig();

        // Act
        $rules = $aclConfig->getRules();

        // Assert
        $this->assertContains(
            [
                'bundle' => 'security-merchant-portal-gui',
                'controller' => '*',
                'action' => '*',
                'type' => 'allow',
            ],
            $rules,
        );
    }

    public function testGetRulesAllowsTheCaptchaChallengeEndpoints(): void
    {
        // Arrange
        $aclConfig = new AclConfig();

        // Act
        $rules = $aclConfig->getRules();

        // Assert
        foreach (['challenge', 'merchant-portal-challenge'] as $controller) {
            $this->assertContains(
                [
                    'bundle' => 'captcha',
                    'controller' => $controller,
                    'action' => 'index',
                    'type' => 'allow',
                ],
                $rules,
            );
        }
    }

    public function testGetCredentialsIncludesTheYvesSystemGatewayCredentials(): void
    {
        // Arrange
        $aclConfig = new AclConfig();

        // Act
        $credentials = $aclConfig->getCredentials();

        // Assert
        $this->assertSame('gateway', $credentials['yves_system']['rules'][0]['controller']);
    }

    public function testGetUserRuleWhitelistIncludesTheApplicationWildcardRule(): void
    {
        // Arrange
        $aclConfig = new AclConfig();

        // Act
        $userRuleWhitelist = $aclConfig->getUserRuleWhitelist();

        // Assert
        $this->assertContains(
            [
                'bundle' => 'application',
                'controller' => '*',
                'action' => '*',
                'type' => 'allow',
            ],
            $userRuleWhitelist,
        );
    }
}
