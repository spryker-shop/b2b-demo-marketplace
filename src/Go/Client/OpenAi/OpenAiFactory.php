<?php
namespace Go\Client\OpenAi;

use Go\Client\OpenAi\Reader\ModelResponse;
use Go\Client\OpenAi\Reader\ModelResponseInterface;
use Go\Client\OpenAi\Writer\SchemaUploader;
use Spryker\Client\Kernel\AbstractFactory;
use GuzzleHttp\Client as GuzzleHttpClient;

/**
 * @method \Go\Client\OpenAi\OpenAiConfig getConfig()
 */
class OpenAiFactory extends AbstractFactory
{
    public function createModelResponse(): ModelResponseInterface
    {
        return new ModelResponse(
            $this->getConfig()->getDefaultApiKey(),
            $this->getConfig()->getDefaultModel(),
            $this->getConfig()->getDefaultTimeout(),
            $this->getHttpClient(),
            $this->createSchemaUploader()
        );
    }

    public function createSchemaUploader(): SchemaUploader
    {
        return new SchemaUploader(
            $this->getConfig()->getDefaultApiKey(),
            $this->getConfig()->getDefaultModel(),
            $this->getConfig()->getDefaultTimeout(),
            $this->getHttpClient(),
        );
    }

    public function getHttpClient(): GuzzleHttpClient
    {
        return $this->getProvidedDependency(OpenAiDependencyProvider::CLIENT_HTTP);
    }
}

