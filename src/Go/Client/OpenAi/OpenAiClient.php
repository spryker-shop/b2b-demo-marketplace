<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Client\OpenAi;

use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \Go\Client\OpenAi\OpenAiFactory getFactory()
 */
class OpenAiClient extends AbstractClient implements OpenAiClientInterface
{
    public function createResponse(array $messages, ?string $instructions = null, array $tools = []): array
    {
        return $this->getFactory()
            ->createModelResponse()
            ->create($messages, $instructions);
    }

    public function createResponseForAgent(array $messages): array
    {
        return $this->getFactory()
            ->createModelResponse()
            ->createForAgent($messages);
    }

    public function uploadSchema(): array
    {
        return $this->getFactory()->createSchemaUploader()->upload();
    }

    public function listVectorStoreFiles(): array
    {
        return $this->getFactory()->createSchemaUploader()->listVectorStoreFiles();
    }

    public function deleteVectorStoreFiles(): void
    {
        $this->getFactory()->createSchemaUploader()->deleteVectorStoreFiles();
    }
}
