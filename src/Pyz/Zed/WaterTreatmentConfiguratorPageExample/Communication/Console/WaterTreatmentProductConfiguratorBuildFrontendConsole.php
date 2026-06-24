<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\WaterTreatmentConfiguratorPageExample\Communication\Console;

use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \Pyz\Zed\WaterTreatmentConfiguratorPageExample\Business\WaterTreatmentConfiguratorPageExampleFacadeInterface getFacade()
 */
class WaterTreatmentProductConfiguratorBuildFrontendConsole extends Console
{
    /**
     * @var string
     */
    public const COMMAND_NAME = 'frontend:water-treatment-product-configurator:build';

    /**
     * @var string
     */
    public const DESCRIPTION = 'This command will build Water Treatment Product Configurator frontend.';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME);
        $this->setDescription(static::DESCRIPTION);

        parent::configure();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        $this->info('Build Water Treatment Product Configurator frontend');

        if ($this->getFacade()->buildProductConfigurationFrontend($this->getMessenger())) {
            return static::CODE_SUCCESS;
        }

        return static::CODE_ERROR;
    }
}
