<?php

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
