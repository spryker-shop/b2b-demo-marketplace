<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\TenantAssigner;

use Spryker\Zed\Kernel\AbstractBundleConfig;

class TenantAssignerConfig extends AbstractBundleConfig
{
    /**
     * @var array<string>
     */
    public const AVAILABLE_TENANTS = [
        'tenant_us' => 'United States',
        'tenant_de' => 'Germany',
        'tenant_uk' => 'United Kingdom',
        'tenant_fr' => 'France',
        'tenant_es' => 'Spain',
    ];

    /**
     * @var int
     */
    public const DEFAULT_PAGE_SIZE = 20;

    /**
     * @var int
     */
    public const MAX_PAGE_SIZE = 100;

    /**
     * @var string
     */
    public const TENANT_COLUMN_NAME = 'id_tenant';

    /**
     * @return array<string, string>
     */
    public function getAvailableTenants(): array
    {
        return static::AVAILABLE_TENANTS;
    }

    /**
     * @return int
     */
    public function getDefaultPageSize(): int
    {
        return static::DEFAULT_PAGE_SIZE;
    }

    /**
     * @return int
     */
    public function getMaxPageSize(): int
    {
        return static::MAX_PAGE_SIZE;
    }

    /**
     * @return string
     */
    public function getTenantColumnName(): string
    {
        return static::TENANT_COLUMN_NAME;
    }
}
