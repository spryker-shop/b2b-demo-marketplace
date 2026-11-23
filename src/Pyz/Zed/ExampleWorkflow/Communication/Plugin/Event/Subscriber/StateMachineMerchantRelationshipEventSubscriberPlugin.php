<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ExampleWorkflow\Communication\Plugin\Event\Subscriber;

use Pyz\Zed\ExampleWorkflow\Communication\Plugin\Event\Listener\StateMachineMerchantRelationshipEventListenerPlugin;
use Pyz\Zed\ExampleWorkflow\ExampleWorkflowConfig;
use Spryker\Zed\Event\Dependency\EventCollectionInterface;
use Spryker\Zed\Event\Dependency\Plugin\EventSubscriberInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Pyz\Zed\ExampleWorkflow\Communication\ExampleWorkflowCommunicationFactory getFactory()
 * @method \Pyz\Zed\ExampleWorkflow\ExampleWorkflowConfig getConfig()
 */
class StateMachineMerchantRelationshipEventSubscriberPlugin extends AbstractPlugin implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return \Spryker\Zed\Event\Dependency\EventCollectionInterface
     */
    public function getSubscribedEvents(EventCollectionInterface $eventCollection)
    {
        $eventCollection->addListenerQueued(
            ExampleWorkflowConfig::EVENT_MERCHANT_RELATIONSHIP_CREATE,
            new StateMachineMerchantRelationshipEventListenerPlugin(),
        );

        return $eventCollection;
    }
}
