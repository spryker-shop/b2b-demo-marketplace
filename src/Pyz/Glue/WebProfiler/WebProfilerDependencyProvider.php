<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Glue\WebProfiler;

use Spryker\Glue\Http\Plugin\Twig\HttpKernelTwigPlugin;
use Spryker\Glue\Http\Plugin\Twig\RuntimeLoaderTwigPlugin;
use Spryker\Glue\Http\Plugin\WebProfiler\WebProfilerExternalHttpDataCollectorPlugin;
use Spryker\Glue\Profiler\Plugin\WebProfiler\WebProfilerProfilerDataCollectorPlugin;
use Spryker\Glue\Redis\Plugin\WebProfiler\WebProfilerRedisDataCollectorPlugin;
use Spryker\Glue\SearchElasticsearch\Plugin\WebProfiler\WebProfilerElasticsearchDataCollectorPlugin;
use Spryker\Glue\WebProfiler\Plugin\WebProfiler\WebProfilerConfigDataCollectorPlugin;
use Spryker\Glue\WebProfiler\Plugin\WebProfiler\WebProfilerExceptionDataCollectorPlugin;
use Spryker\Glue\WebProfiler\Plugin\WebProfiler\WebProfilerLoggerDataCollectorPlugin;
use Spryker\Glue\WebProfiler\Plugin\WebProfiler\WebProfilerMemoryDataCollectorPlugin;
use Spryker\Glue\WebProfiler\Plugin\WebProfiler\WebProfilerRequestDataCollectorPlugin;
use Spryker\Glue\WebProfiler\Plugin\WebProfiler\WebProfilerTimeDataCollectorPlugin;
use Spryker\Glue\WebProfiler\WebProfilerDependencyProvider as SprykerWebProfilerDependencyProvider;
use Spryker\Glue\ZedRequest\Plugin\WebProfiler\WebProfilerZedRequestDataCollectorPlugin;
use Spryker\Shared\Twig\Plugin\RoutingTwigPlugin;

class WebProfilerDependencyProvider extends SprykerWebProfilerDependencyProvider
{
    /**
     * @return array<\Spryker\Shared\WebProfilerExtension\Dependency\Plugin\WebProfilerDataCollectorPluginInterface>
     */
    protected function getDataCollectorPlugins(): array
    {
        $plugins = [
            new WebProfilerRequestDataCollectorPlugin(),
            new WebProfilerMemoryDataCollectorPlugin(),
            new WebProfilerTimeDataCollectorPlugin(),
            new WebProfilerConfigDataCollectorPlugin(),
            new WebProfilerRedisDataCollectorPlugin(),
            new WebProfilerElasticsearchDataCollectorPlugin(),
            new WebProfilerZedRequestDataCollectorPlugin(),
            new WebProfilerExternalHttpDataCollectorPlugin(),
            new WebProfilerExceptionDataCollectorPlugin(),
            new WebProfilerLoggerDataCollectorPlugin(),
        ];

        if (class_exists(WebProfilerProfilerDataCollectorPlugin::class)) {
            $plugins[] = new WebProfilerProfilerDataCollectorPlugin();
        }

        return $plugins;
    }

    /**
     * @return array<\Spryker\Shared\TwigExtension\Dependency\Plugin\TwigPluginInterface>
     */
    protected function getTwigPlugins(): array
    {
        return [
            new HttpKernelTwigPlugin(),
            new RoutingTwigPlugin(),
            new RuntimeLoaderTwigPlugin(),
        ];
    }
}
