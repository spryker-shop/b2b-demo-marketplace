<?php

namespace Pyz\Zed\Queue\Business;

use Psr\Log\LoggerInterface;
use Pyz\Client\RabbitMq\RabbitMqClientInterface;
use Pyz\Zed\Queue\Business\Strategy\OrderedQueuesStrategy;
use Pyz\Zed\Queue\Business\Strategy\QueueProcessingStrategyInterface;
use Pyz\Zed\Queue\Business\SystemResources\SystemResourcesManager;
use Pyz\Zed\Queue\Business\SystemResources\SystemResourcesManagerInterface;
use Pyz\Zed\Queue\Business\Worker\WorkerV2;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \Pyz\Zed\Queue\QueueConfig getConfig()
 */
class QueueBusinessFactory extends \Spryker\Zed\Queue\Business\QueueBusinessFactory
{
    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return \Spryker\Zed\Queue\Business\Worker\WorkerInterface
     */
    public function createWorker(OutputInterface $output)
    {
        return new WorkerV2(
            $this->createProcessManager(),
            $this->getConfig(),
            $this->getQueueClient(),
            $this->getQueueNames(),
            $this->createSimpleQueueStrategy($output),
            $this->createQueueWorkerSignalDispatcher(),
            $this->createSystemResourcesManager($output),
            $this->getConsoleLogger($output),
        );
    }

    public function createSimpleQueueStrategy(OutputInterface $output): QueueProcessingStrategyInterface
    {
        return new OrderedQueuesStrategy(
            $this->getMQClient(),
            $this->getQueueNames(),
            $this->getConsoleLogger($output),
        );
    }

    protected function createSystemResourcesManager(OutputInterface $output): SystemResourcesManagerInterface
    {
        return new SystemResourcesManager(
            $this->getConfig(),
            $this->getConsoleLogger($output),
        );
    }

    protected function getConsoleLogger(OutputInterface $output): LoggerInterface
    {
        return $this->createConsoleLogger($output);
    }

    protected function createConsoleLogger(OutputInterface $output): ConsoleLogger
    {
        return new ConsoleLogger($output);
    }

    /**
     * @return \Pyz\Client\RabbitMq\RabbitMqClientInterface
     */
    public function getMQClient(): RabbitMqClientInterface
    {
        return $this->getContainer()->getLocator()->rabbitMq()->client();
    }
}
