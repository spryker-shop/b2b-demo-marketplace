<?php

namespace Go\Zed\ProductDiscontinued\Communication\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeactivateDiscontinuedProductsConsole extends \Spryker\Zed\ProductDiscontinued\Communication\Console\DeactivateDiscontinuedProductsConsole
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
        $messenger = $this->getMessenger();
        $this->forEachTenant(function () use ($messenger): void {
            $this->getFacade()->deactivateDiscontinuedProducts($messenger);
        });

        return static::CODE_SUCCESS;
    }
}
