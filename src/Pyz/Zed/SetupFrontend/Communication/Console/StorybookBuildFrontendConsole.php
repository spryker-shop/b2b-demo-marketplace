<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 * 
 * @disclaimer This class is a prototype implementation.
 * It will be moved to the core module spryker/setup-frontend after the prototype confirmation.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SetupFrontend\Communication\Console;

use Generated\Shared\Transfer\SetupFrontendConfigurationTransfer;
use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \Pyz\Zed\SetupFrontend\Business\SetupFrontendFacadeInterface getFacade()
 */
class StorybookBuildFrontendConsole extends Console
{
    /**
     * @var string
     */
    public const COMMAND_NAME = 'frontend:storybook:build';

    /**
     * @var string
     */
    public const DESCRIPTION = 'This command will build the Storybook static assets.';

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
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->info('Build Storybook frontend');

        if ($this->getFacade()->buildStorybookFrontend($this->getMessenger(), new SetupFrontendConfigurationTransfer())) {
            return static::CODE_SUCCESS;
        }

        return static::CODE_ERROR;
    }
}
