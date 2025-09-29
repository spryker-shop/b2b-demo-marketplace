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
class OpenAiRestApiSchemaUploadConsole extends Console
{
    public const COMMAND_NAME = 'open-ai:backoffice-rest-api-schema-upload';

    public const DESCRIPTION = 'Uploads the Backoffice OpenAPI schema to the OpenAI vector store.';

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
            $result = $client->uploadSchema();
            $output->writeln('<info>Vector Store ID:</info> ' . $result['vector_store_id']);
            $output->writeln('<info>File ID:</info> ' . $result['file_id']);

            return static::CODE_SUCCESS;
        } catch (Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return static::CODE_ERROR;
        }
    }
}
