<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Plmb\Zed\User;

use Pyz\Zed\User\UserConfig as PyzUserConfig;

/**
 * Adds a Back Office / Merchant Portal user per project merchant.
 *
 * The `merchant-user` data importer only LINKS an existing Zed user to a merchant - it does not
 * create one - so a merchant listed in merchant_user.csv whose username is not seeded here fails
 * the import with `User with username "..." is not found.`
 *
 * Extends the Pyz config (not the Spryker one) and merges onto the inherited list so the demoshop's
 * admin/agent accounts survive - dropping them would lock the Back Office.
 *
 * NOTE: the `change123` password matches the inherited demoshop convention. Every one of these
 * accounts, demo and project alike, must be rotated before go-live (see curate-golive-data).
 */
class UserConfig extends PyzUserConfig
{
    /**
     * @return array<array<string, mixed>>
     */
    public function getInstallerUsers(): array
    {
        return array_merge(parent::getInstallerUsers(), [
            [
                'firstName' => 'Agnieszka',
                'lastName' => 'Kowalczyk',
                'password' => 'change123',
                'username' => 'agnieszka@nordwerk.example',
                'localeName' => 'en_US',
            ],
            [
                'firstName' => 'Tomasz',
                'lastName' => 'Zielinski',
                'password' => 'change123',
                'username' => 'tomasz@vistula-tools.example',
                'localeName' => 'en_US',
            ],
            [
                'firstName' => 'Oksana',
                'lastName' => 'Melnyk',
                'password' => 'change123',
                'username' => 'oksana@karpat-industrial.example',
                'localeName' => 'en_US',
            ],
        ]);
    }
}
