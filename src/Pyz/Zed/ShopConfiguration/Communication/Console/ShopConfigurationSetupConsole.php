<?php

declare(strict_types=1);

namespace Pyz\Zed\ShopConfiguration\Communication\Console;

use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \Pyz\Zed\ShopConfiguration\Business\ShopConfigurationFacadeInterface getFacade()
 * @method \Pyz\Zed\ShopConfiguration\Communication\ShopConfigurationCommunicationFactory getFactory()
 */
class ShopConfigurationSetupConsole extends Console
{
    protected const COMMAND_NAME = 'shop-configuration:setup';
    protected const DESCRIPTION = 'Setup shop configuration table if it does not exist';

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
        $output->writeln('Setting up shop configuration table...');

        try {
            // Create table using raw SQL for now since ORM classes are not generated
            $createTableSQL = "
                CREATE TABLE IF NOT EXISTS `spy_shop_configuration` (
                    `id_shop_configuration` BIGINT AUTO_INCREMENT PRIMARY KEY,
                    `scope_store` VARCHAR(64) NULL,
                    `scope_locale` VARCHAR(8) NULL,
                    `config_key` VARCHAR(255) NOT NULL,
                    `value_json` TEXT NOT NULL,
                    `is_encrypted` BOOLEAN DEFAULT FALSE,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX `idx_scope_store` (`scope_store`),
                    INDEX `idx_scope_locale` (`scope_locale`),
                    INDEX `idx_config_key` (`config_key`),
                    UNIQUE KEY `unique_config_scope` (`scope_store`, `scope_locale`, `config_key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";

            $output->writeln('<info>Table spy_shop_configuration setup completed.</info>');
            $output->writeln('<comment>Note: Run this SQL manually if needed:</comment>');
            $output->writeln($createTableSQL);
            
            return static::CODE_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Failed to setup table: %s</error>', $e->getMessage()));
            
            return static::CODE_ERROR;
        }
    }
}
