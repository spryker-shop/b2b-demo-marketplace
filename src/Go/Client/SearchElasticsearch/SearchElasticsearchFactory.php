<?php

namespace Go\Client\SearchElasticsearch;

use Go\Client\SearchElasticsearch\Index\IndexNameResolver\IndexNameResolver;

class SearchElasticsearchFactory extends \Spryker\Client\SearchElasticsearch\SearchElasticsearchFactory
{
    /**
     * @return \Spryker\Client\SearchElasticsearch\Index\IndexNameResolver\IndexNameResolverInterface
     */
    public function createIndexNameResolver(): \Spryker\Client\SearchElasticsearch\Index\IndexNameResolver\IndexNameResolverInterface
    {
        return new IndexNameResolver(
            $this->getStoreClient(),
            $this->getConfig(),
        );
    }
}
