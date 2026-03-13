<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant\Persistence;

use Demo\Zed\BackofficeAssistant\Persistence\Propel\Mapper\BackofficeAssistantMapper;
use Orm\Zed\BackofficeAssistant\Persistence\DemoBackofficeAssistantConversationQuery;
use Spryker\Zed\Kernel\Persistence\AbstractPersistenceFactory;

/**
 * @method \Demo\Zed\BackofficeAssistant\Persistence\BackofficeAssistantEntityManagerInterface getEntityManager()
 * @method \Demo\Zed\BackofficeAssistant\Persistence\BackofficeAssistantRepositoryInterface getRepository()
 */
class BackofficeAssistantPersistenceFactory extends AbstractPersistenceFactory
{
    /**
     * @return \Orm\Zed\BackofficeAssistant\Persistence\DemoBackofficeAssistantConversationQuery
     */
    public function createBackofficeAssistantConversationQuery(): DemoBackofficeAssistantConversationQuery
    {
        return DemoBackofficeAssistantConversationQuery::create();
    }

    /**
     * @return \Demo\Zed\BackofficeAssistant\Persistence\Propel\Mapper\BackofficeAssistantMapper
     */
    public function createMapper(): BackofficeAssistantMapper
    {
        return new BackofficeAssistantMapper();
    }
}
