<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\WaterTreatmentConfiguratorPageExample\Business\Builder;

use Psr\Log\LoggerInterface;

interface FrontendBuilderInterface
{
    /**
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return bool
     */
    public function build(LoggerInterface $logger): bool;
}
