<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\WaterTreatmentConfigurator\Business\Builder;

use Psr\Log\LoggerInterface;
use Pyz\Zed\WaterTreatmentConfigurator\WaterTreatmentConfiguratorConfig;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class FrontendBuilder implements FrontendBuilderInterface
{
    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Pyz\Zed\WaterTreatmentConfigurator\WaterTreatmentConfiguratorConfig
     */
    protected $config;

    /**
     * @param \Symfony\Component\Filesystem\Filesystem $filesystem
     * @param \Pyz\Zed\WaterTreatmentConfigurator\WaterTreatmentConfiguratorConfig $config
     */
    public function __construct(Filesystem $filesystem, WaterTreatmentConfiguratorConfig $config)
    {
        $this->filesystem = $filesystem;
        $this->config = $config;
    }

    /**
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return bool
     */
    public function build(LoggerInterface $logger): bool
    {
        try {
            $this->filesystem->mirror($this->config->getFrontendOriginPath(), $this->config->getFrontendTargetPath());
        } catch (IOException $exception) {
            $logger->error($exception->getMessage());

            return false;
        }

        return true;
    }
}
