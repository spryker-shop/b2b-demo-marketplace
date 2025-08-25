<?php

namespace Pyz\Zed\SearchElasticsearch\Business;

use Pyz\Zed\SearchElasticsearch\Business\SourceIdentifier\SourceIdentifier;
use Spryker\Zed\SearchElasticsearch\Business\SourceIdentifier\SourceIdentifierInterface;

class SearchElasticsearchBusinessFactory extends \Spryker\Zed\SearchElasticsearch\Business\SearchElasticsearchBusinessFactory
{
    /**
     * @return \Spryker\Zed\SearchElasticsearch\Business\SourceIdentifier\SourceIdentifierInterface
     */
    public function createSourceIdentifier(): SourceIdentifierInterface
    {
        return new SourceIdentifier(
            $this->getConfig(),
        );
    }
}
