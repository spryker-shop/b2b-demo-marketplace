<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\CustomerNote;

use Generated\Shared\Transfer\SpyCustomerNoteEntityTransfer;
use Orm\Zed\CustomerNote\Persistence\Map\SpyCustomerNoteTableMap;
use Spryker\Zed\CustomerNote\CustomerNoteConfig as SprykerCustomerNoteConfig;

class CustomerNoteConfig extends SprykerCustomerNoteConfig
{
    /**
     * @return array<string, string>
     */
    public function getCustomerNoteCollectionSortableFieldMap(): array
    {
        return [
            SpyCustomerNoteEntityTransfer::CREATED_AT => SpyCustomerNoteTableMap::COL_CREATED_AT,
            SpyCustomerNoteEntityTransfer::USERNAME => SpyCustomerNoteTableMap::COL_USERNAME,
        ];
    }
}
