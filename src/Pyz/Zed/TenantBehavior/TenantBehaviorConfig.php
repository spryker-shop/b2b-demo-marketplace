<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\TenantBehavior;

use Spryker\Zed\Kernel\AbstractBundleConfig;

class TenantBehaviorConfig extends AbstractBundleConfig
{
    /**
     * @var string
     */
    public const TENANT_ID_COLUMN_NAME = 'id_tenant';

    /**
     * @var int
     */
    public const TENANT_ID_COLUMN_SIZE = 36; // UUID string length

    /**
     * @var string
     */
    public const SERVICE_CONTAINER_TENANT_ID_KEY = 'idTenant';

    /**
     * @return string
     */
    public function getTenantIdColumnName(): string
    {
        return static::TENANT_ID_COLUMN_NAME;
    }

    /**
     * @return string
     */
    public function getServiceContainerTenantIdKey(): string
    {
        return static::SERVICE_CONTAINER_TENANT_ID_KEY;
    }

    /**
     * @return int
     */
    public function getTenantIdColumnSize(): int
    {
        return static::TENANT_ID_COLUMN_SIZE;
    }
}
