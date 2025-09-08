<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\TenantBehavior;

use Spryker\Zed\Kernel\AbstractBundleConfig;

class TenantBehaviorConfig extends AbstractBundleConfig
{
    /**
     * @var string
     */
    public const TENANT_ID_COLUMN_NAME = 'tenant_reference';

    /**
     * @var int
     */
    public const TENANT_ID_COLUMN_SIZE = 36; // UUID string length

    /**
     * @return string
     */
    public function getTenantReferenceColumnName(): string
    {
        return static::TENANT_ID_COLUMN_NAME;
    }

    /**
     * @return int
     */
    public function getTenantIdColumnSize(): int
    {
        return static::TENANT_ID_COLUMN_SIZE;
    }
}
