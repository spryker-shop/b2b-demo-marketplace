<?php

namespace Pyz\Zed\Queue\Business;

use Pyz\Zed\Queue\Business\Worker\Worker;
use Symfony\Component\Console\Output\OutputInterface;

class QueueBusinessFactory extends \Spryker\Zed\Queue\Business\QueueBusinessFactory
{
    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return \Spryker\Zed\Queue\Business\Worker\Worker
     */
    public function createWorker(OutputInterface $output)
    {
        return new Worker(
            $this->createProcessManager(),
            $this->getConfig(),
            $this->createWorkerProgressbar($output),
            $this->getQueueClient(),
            $this->getQueueNames(),
            $this->createQueueWorkerSignalDispatcher(),
            $this->createQueueConfigReader(),
            $this->getQueueMessageCheckerPlugins(),
        );
    }
}
