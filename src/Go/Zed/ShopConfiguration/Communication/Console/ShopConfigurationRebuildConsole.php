<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Communication\Console;

use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \Go\Zed\ShopConfiguration\Business\ShopConfigurationFacadeInterface getFacade()
 */
class ShopConfigurationRebuildConsole extends Console
{
    protected const COMMAND_NAME = 'shop-configuration:rebuild';
    protected const DESCRIPTION = 'Rebuild shop configurations from files';

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
        $output->writeln('Rebuilding shop configurations from files...');

        try {
            $this->getFacade()->rebuildConfigurationFromFiles();
            $output->writeln('<info>Shop configurations rebuilt successfully from files.</info>');

            return static::CODE_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Failed to rebuild configurations: %s</error>', $e->getMessage()));

            return static::CODE_ERROR;
        }
    }
}
