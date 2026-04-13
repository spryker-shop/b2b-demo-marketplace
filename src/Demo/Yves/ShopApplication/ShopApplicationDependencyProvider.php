<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Yves\ShopApplication;

use Pyz\Yves\ShopApplication\ShopApplicationDependencyProvider as PyzShopApplicationDependencyProvider;
use SprykerFeature\Yves\AiCommerce\SearchByImage\Widget\ImageSearchAiWidget;

class ShopApplicationDependencyProvider extends PyzShopApplicationDependencyProvider
{
    /**
     * @return array<string>
     */
    protected function getGlobalWidgets(): array
    {
        return array_merge(parent::getGlobalWidgets(), [
            ImageSearchAiWidget::class,
        ]);
    }
}
