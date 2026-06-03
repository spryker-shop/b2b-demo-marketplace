<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\Price\Business;

use Spryker\Zed\Price\Business\PriceFacadeInterface as SprykerPriceFacadeInterface;

interface PriceFacadeInterface extends SprykerPriceFacadeInterface
{
    /**
     * Specification:
     *  - Returns default price mode as configured in store.
     *
     * @api
     */
    public function getDefaultPriceMode(): string;

    /**
     * Specification:
     *  - Returns net price mode identifier.
     *
     * @api
     */
    public function getNetPriceModeIdentifier(): string;

    /**
     * Specification:
     *  - Returns gross price mode identifier.
     *
     * @api
     */
    public function getGrossPriceModeIdentifier(): string;

    /**
     * Specification:
     * - Returns cost price mode identifier.
     *
     * @api
     */
    public function getCostPriceModeIdentifier(): string;
}
