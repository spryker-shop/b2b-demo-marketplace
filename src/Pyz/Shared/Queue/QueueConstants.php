<?php

declare(strict_types = 1);

namespace Pyz\Shared\Queue;

use Spryker\Shared\Queue\QueueConstants as SprykerQueueConstants;

interface QueueConstants extends SprykerQueueConstants
{
    /**
     * Specification:
     * - Max concurrent PHP processes for all queues/stores
     *
     * @api
     *
     * @var string
     */
    public const QUEUE_WORKER_MAX_PROCESSES = 'QUEUE_WORKER_MAX_PROCESSES';

    /**
     * Specification:
     * - Whether to ignore cases when system free memory can't be detected/read or parsed
     * - For use in \Pyz\Zed\Queue\Business\SystemResources\SystemResourcesManager::enoughResources
     *
     * @api
     *
     * @var string
     */
    public const QUEUE_WORKER_IGNORE_MEM_READ_FAILURE = 'QUEUE_WORKER_IGNORE_MEM_READ_FAILURE';

    /**
     * Specification:
     * - Defines free memory buffer for reliability, MBs.
     *
     * @api
     *
     * @var string
     */
    public const QUEUE_WORKER_FREE_MEMORY_BUFFER = 'QUEUE_WORKER_FREE_MEMORY_BUFFER';

    /**
     * Specification:
     * - Defines timeout for memory read process(command), seconds
     *
     * @api
     *
     * @var string
     */
    public const QUEUE_WORKER_MEMORY_READ_PROCESS_TIMEOUT = 'QUEUE_WORKER_MEMORY_READ_PROCESS_TIMEOUT';
}
