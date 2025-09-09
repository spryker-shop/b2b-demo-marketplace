<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Communication\Console;

use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \Go\Zed\ShopConfiguration\Business\ShopConfigurationFacadeInterface getFacade()
 * @method \Go\Zed\ShopConfiguration\Communication\ShopConfigurationCommunicationFactory getFactory()
 */
class ShopConfigurationPublishAllConsole extends Console
{
    protected const COMMAND_NAME = 'shop-configuration:publish-all';
    protected const DESCRIPTION = 'Publish shop configurations for all stores and locales';

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
        $output->writeln('Publishing shop configurations for all stores and locales...');

        try {
            // Get all stores from the store facade
            $storeFacade = $this->getFactory()->getStoreFacade();
            $storeTransfers = $storeFacade->getAllStores();

            $stores = [];
            $locales = [];

            foreach ($storeTransfers as $storeTransfer) {
                $stores[] = $storeTransfer->getName();
                foreach ($storeTransfer->getAvailableLocaleIsoCodes() as $localeCode) {
                    if (!in_array($localeCode, $locales, true)) {
                        $locales[] = $localeCode;
                    }
                }
            }

            $this->getFacade()->publishConfigurationForStoresAndLocales($stores, $locales);

            $output->writeln(sprintf(
                '<info>Shop configurations published successfully for %d stores and %d locales.</info>',
                count($stores),
                count($locales)
            ));

            return static::CODE_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Failed to publish configurations: %s</error>', $e->getMessage()));

            return static::CODE_ERROR;
        }
    }
}
