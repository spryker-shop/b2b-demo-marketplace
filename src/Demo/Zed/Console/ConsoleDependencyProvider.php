<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\Console;

use Pyz\Zed\Console\ConsoleDependencyProvider as PyzConsoleDependencyProvider;
use Spryker\Zed\Kernel\Container;
use SprykerEco\Zed\AmazonQuicksight\Communication\Console\QuicksightUserSyncSaveConsole;
use SprykerFeature\Zed\AiCommerce\Communication\Console\KnowledgeDocumentReindexConsole;
use SprykerFeature\Zed\AiCommerce\Communication\Console\KnowledgeDocumentSetupConsole;
use SprykerFeature\Zed\AiCommerce\Communication\Console\KnowledgeDocumentStatusRefreshConsole;

class ConsoleDependencyProvider extends PyzConsoleDependencyProvider
{
    /**
     * @return array<\Symfony\Component\Console\Command\Command>
     */
    protected function getConsoleCommands(Container $container): array
    {
        $commands = parent::getConsoleCommands($container);

        $commands[] = new QuicksightUserSyncSaveConsole();
        $commands[] = new KnowledgeDocumentReindexConsole();
        $commands[] = new KnowledgeDocumentStatusRefreshConsole();
        $commands[] = new KnowledgeDocumentSetupConsole();

        return $commands;
    }
}
