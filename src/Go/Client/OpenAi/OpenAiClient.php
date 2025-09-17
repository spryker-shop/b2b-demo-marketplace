<?php
namespace Go\Client\OpenAi;

use GuzzleHttp\Client;
use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \Go\Client\OpenAi\OpenAiFactory getFactory()
 */
class OpenAiClient extends AbstractClient implements OpenAiClientInterface
{
    public function createResponse(array $messages, string $instructions = null, array $tools = []): array
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
}
