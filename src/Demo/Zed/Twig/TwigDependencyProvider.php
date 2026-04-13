<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\Twig;

use Pyz\Zed\Twig\TwigDependencyProvider as PyzTwigDependencyProvider;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\Twig\AiCommerceTwigPlugin;

class TwigDependencyProvider extends PyzTwigDependencyProvider
{
    protected function getTwigPlugins(): array
    {
        return array_merge(parent::getTwigPlugins(), [
            new AiCommerceTwigPlugin(),
        ]);
    }
}
