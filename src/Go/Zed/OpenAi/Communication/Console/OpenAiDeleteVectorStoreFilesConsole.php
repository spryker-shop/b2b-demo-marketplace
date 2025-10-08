<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\OpenAi\Communication\Console;

use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * @method \Go\Zed\OpenAi\Communication\OpenAiCommunicationFactory getFactory()
 */
class OpenAiDeleteVectorStoreFilesConsole extends Console
{
    public const COMMAND_NAME = 'open-ai:vector-store:delete-files';

    public const DESCRIPTION = 'Delete files in the OpenAI vector store.';

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME);
        $this->setDescription(static::DESCRIPTION);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $client = $this->getFactory()->getOpenAiClient();

        try {
            $result = $client->deleteVectorStoreFiles() ?? [];

            $output->writeln('<info>Files deleted.</info>');

            return static::CODE_SUCCESS;
        } catch (Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return static::CODE_ERROR;
        }
    }
}
