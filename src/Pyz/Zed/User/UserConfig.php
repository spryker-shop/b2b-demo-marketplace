<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\User;

use Spryker\Zed\User\UserConfig as SprykerUserConfig;

class UserConfig extends SprykerUserConfig
{
    /**
     * @return array<array<string, mixed>>
     */
    public function getInstallerUsers(): array
    {
        return [
            [
                'firstName' => 'Admin',
                'lastName' => 'Spryker',
                'username' => 'admin@spryker.com',
                'password' => 'change123',
                'isAgent' => true,
                'localeName' => 'en_US',
            ],
            [
                'firstName' => 'Admin',
                'lastName' => 'German',
                'password' => 'change123',
                'username' => 'admin_de@spryker.com',
                'localeName' => 'de_DE',
            ],
            [
                'firstName' => 'Harald',
                'lastName' => 'Schmidt',
                'password' => 'change123',
                'username' => 'harald@spryker.com',
            ],
            [
                'firstName' => 'Richard',
                'lastName' => 'Gere',
                'password' => 'change123',
                'username' => 'richard@spryker.com',
            ],
            [
                'firstName' => 'Martha',
                'lastName' => 'Farmer',
                'password' => 'change123',
                'username' => 'martha@office-king.nl',
            ],
            [
                'firstName' => 'Jason',
                'lastName' => 'Weidmann',
                'password' => 'change123',
                'username' => 'jason.weidmann@budgetstationery.com',
            ],
            [
                'firstName' => 'Michele',
                'lastName' => 'Nemeth',
                'password' => 'change123',
                'username' => 'michele@computer-experts.com',
            ],
            [
                'firstName' => 'Vitaliy',
                'lastName' => 'Smith',
                'password' => 'change123',
                'username' => 'agent123@spryker.com',
                'isAgent' => 1,
            ],
            [
                'firstName' => 'Agent',
                'lastName' => 'Merchant',
                'password' => 'change123',
                'username' => 'agent-merchant@spryker.com',
                'isMerchantAgent' => 1,
                'localeName' => 'en_US',
            ],
        ];
    }
}
