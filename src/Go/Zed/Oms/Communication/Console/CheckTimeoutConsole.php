<?php

namespace Go\Zed\Oms\Communication\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckTimeoutConsole extends \Spryker\Zed\Oms\Communication\Console\CheckTimeoutConsole
{
    use \Go\Zed\TenantBehavior\Communication\Console\TenantIterationTrait;
    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $omsCheckTimeoutsQueryCriteriaTransfer = $this->buildOmsCheckTimeoutsQueryCriteriaTransfer($input);

        $this->forEachTenant(function () use ($omsCheckTimeoutsQueryCriteriaTransfer): void {
            $this->getFacade()->checkTimeouts([], $omsCheckTimeoutsQueryCriteriaTransfer);
        });

        return static::CODE_SUCCESS;
    }
}
