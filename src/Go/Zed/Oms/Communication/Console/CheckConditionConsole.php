<?php

namespace Go\Zed\Oms\Communication\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckConditionConsole extends \Spryker\Zed\Oms\Communication\Console\CheckConditionConsole
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
        $omsCheckConditionQueryCriteriaTransfer = $this->buildOmsCheckConditionQueryCriteriaTransfer($input);

        $this->forEachTenant(function () use ($omsCheckConditionQueryCriteriaTransfer): void {
            $this->getFacade()->checkConditions([], $omsCheckConditionQueryCriteriaTransfer);
        });

        return static::CODE_SUCCESS;
    }
}
