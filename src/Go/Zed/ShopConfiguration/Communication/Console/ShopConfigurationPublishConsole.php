<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Communication\Console;

use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \Go\Zed\ShopConfiguration\Business\ShopConfigurationFacadeInterface getFacade()
 */
class ShopConfigurationPublishConsole extends Console
{
    public const COMMAND_NAME = 'shop-configuration:publish';
    public const OPTION_STORE = 'store';
    public const OPTION_LOCALE = 'locale';

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME)
            ->setDescription('Publishes effective Shop Configuration for a given store and optional locale to Redis')
            ->addOption(static::OPTION_STORE, null, InputOption::VALUE_REQUIRED, 'Store name (e.g., DE)')
            ->addOption(static::OPTION_LOCALE, null, InputOption::VALUE_OPTIONAL, 'Locale (e.g., de_DE)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $store = (string)$input->getOption(static::OPTION_STORE);
        $locale = $input->getOption(static::OPTION_LOCALE);
        $locale = $locale !== null ? (string)$locale : null;

        if ($store === '') {
            $output->writeln('<error>--store option is required</error>');
            return static::CODE_ERROR;
        }

        try {
            $this->getFacade()->publishConfiguration($store, $locale);
            $output->writeln(sprintf('<info>Published shop configuration for store %s%s</info>', $store, $locale ? ' and locale ' . $locale : ''));

            return static::CODE_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Failed to publish configuration: %s</error>', $e->getMessage()));
            return static::CODE_ERROR;
        }
    }
}
