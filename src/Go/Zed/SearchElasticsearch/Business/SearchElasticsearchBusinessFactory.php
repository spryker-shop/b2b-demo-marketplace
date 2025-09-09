<?php

namespace Go\Zed\SearchElasticsearch\Business;

use Go\Zed\SearchElasticsearch\Business\Index\Index;
use Go\Zed\SearchElasticsearch\Business\Installer\Index\IndexInstallBroker;
use Go\Zed\SearchElasticsearch\Business\SourceIdentifier\SourceIdentifier;
use Spryker\Zed\SearchElasticsearch\Business\Index\IndexInterface;
use Spryker\Zed\SearchElasticsearch\Business\Installer\Index\IndexInstallBrokerInterface;
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

    /**
     * @return \Spryker\Zed\SearchElasticsearch\Business\Index\IndexInterface
     */
    public function createIndex(): IndexInterface
    {
        return new Index(
            $this->getElasticsearchClient(),
            $this->createSourceIdentifier(),
            $this->getConfig(),
            $this->getStoreFacade(),
            $this->getContainer()->getLocator()->tenantBehavior()->facade(),
        );
    }

    /**
     * @return \Spryker\Zed\SearchElasticsearch\Business\Installer\Index\IndexInstallBrokerInterface
     */
    public function createIndexInstallBroker(): IndexInstallBrokerInterface
    {
        return new IndexInstallBroker(
            $this->createIndexDefinitionBuilder(),
            $this->getInstaller(),
            $this->getStoreFacade(),
            $this->getContainer()->getLocator()->tenantBehavior()->facade(),
        );
    }
}
