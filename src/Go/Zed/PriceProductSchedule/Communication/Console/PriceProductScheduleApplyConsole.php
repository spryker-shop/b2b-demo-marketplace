<?php

namespace Go\Zed\PriceProductSchedule\Communication\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PriceProductScheduleApplyConsole extends \Spryker\Zed\PriceProductSchedule\Communication\Console\PriceProductScheduleApplyConsole
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
        $this->forEachTenant(function () use ($input): void {
            $this->getFacade()->applyScheduledPrices($this->getStore($input));
        });

        return static::CODE_SUCCESS;
    }
}
