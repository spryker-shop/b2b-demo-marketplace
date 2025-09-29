<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Client\OpenAi;

use Go\Client\OpenAi\Reader\ModelResponse;
use Go\Client\OpenAi\Reader\ModelResponseInterface;
use Go\Client\OpenAi\Writer\SchemaUploader;
use GuzzleHttp\Client as GuzzleHttpClient;
use Spryker\Client\Kernel\AbstractFactory;

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
            $this->createSchemaUploader(),
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
