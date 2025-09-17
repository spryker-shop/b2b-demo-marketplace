<?php

declare(strict_types=1);

namespace Go\Zed\OpenAi\Communication\Console;

use Go\Zed\OpenAi\Communication\OpenAiCommunicationFactory;
use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method OpenAiCommunicationFactory getFactory()
 */
class OpenAiListVectorStoreFilesConsole extends Console
{
    public const COMMAND_NAME = 'open-ai:vector-store:list-files';
    public const DESCRIPTION = 'Lists files in the OpenAI vector store.';

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
            $result = $client->listVectorStoreFiles() ?? [];

            foreach ($result as $file) {
                $output->writeln(sprintf(
                    "File ID: %s, Filename: %s, Purpose: %s, Status: %s, Created At: %s, Size (bytes): %d",
                    $file['id'] ?? 'N/A',
                    $file['filename'] ?? 'N/A',
                    $file['purpose'] ?? 'N/A',
                    $file['status'] ?? 'N/A',
                    date('Y-m-d H:i:s', $file['created_at']) ?? 'N/A',
                    $file['bytes'] ?? 0
                ));
            }

            return static::CODE_SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return static::CODE_ERROR;
        }
    }
}
