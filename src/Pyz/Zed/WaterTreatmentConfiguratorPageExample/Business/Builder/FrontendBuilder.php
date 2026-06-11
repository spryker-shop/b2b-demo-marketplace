<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\WaterTreatmentConfiguratorPageExample\Business\Builder;

use Psr\Log\LoggerInterface;
use Pyz\Zed\WaterTreatmentConfiguratorPageExample\WaterTreatmentConfiguratorPageExampleConfig;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class FrontendBuilder implements FrontendBuilderInterface
{
    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Pyz\Zed\WaterTreatmentConfiguratorPageExample\WaterTreatmentConfiguratorPageExampleConfig
     */
    protected $config;

    /**
     * @param \Symfony\Component\Filesystem\Filesystem $filesystem
     * @param \Pyz\Zed\WaterTreatmentConfiguratorPageExample\WaterTreatmentConfiguratorPageExampleConfig $config
     */
    public function __construct(Filesystem $filesystem, WaterTreatmentConfiguratorPageExampleConfig $config)
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
