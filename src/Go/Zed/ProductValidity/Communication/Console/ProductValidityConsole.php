<?php

namespace Go\Zed\ProductValidity\Communication\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProductValidityConsole extends \Spryker\Zed\ProductValidity\Communication\Console\ProductValidityConsole
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
        $this->forEachTenant(function (): void {
            $this->getFacade()->checkProductValidityDateRangeAndTouch();
        });

        return static::CODE_SUCCESS;
    }
}
