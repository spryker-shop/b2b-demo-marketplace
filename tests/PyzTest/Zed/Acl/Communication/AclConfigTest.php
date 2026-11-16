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
        $this->assertFalse(Config::hasKey(AclConstants::ACL_DEFAULT_RULES));
        $this->assertFalse(Config::hasKey(AclConstants::ACL_DEFAULT_CREDENTIALS));
        $this->assertFalse(Config::hasKey(AclConstants::ACL_USER_RULE_WHITELIST));
    }

    public function testGetRulesIncludesTheMerchantPortalDefaultRule(): void
    {
        $aclConfig = new AclConfig();

        $this->assertContains(
            [
                'bundle' => 'security-merchant-portal-gui',
                'controller' => '*',
                'action' => '*',
                'type' => 'allow',
            ],
            $aclConfig->getRules(),
        );
    }

    public function testGetCredentialsIncludesTheYvesSystemGatewayCredentials(): void
    {
        $aclConfig = new AclConfig();

        $this->assertSame(
            'gateway',
            $aclConfig->getCredentials()['yves_system']['rules'][0]['controller'],
        );
    }

    public function testGetUserRuleWhitelistIncludesTheApplicationWildcardRule(): void
    {
        $aclConfig = new AclConfig();

        $this->assertContains(
            [
                'bundle' => 'application',
                'controller' => '*',
                'action' => '*',
                'type' => 'allow',
            ],
            $aclConfig->getUserRuleWhitelist(),
        );
    }
}
