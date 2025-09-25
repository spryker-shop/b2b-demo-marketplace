<?php

namespace Go\Zed\OrderMatrix\Communication\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OrderMatrixConsole extends \Spryker\Zed\OrderMatrix\Communication\Console\OrderMatrixConsole
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
            $this->getFacade()->writeOrderMatrix();
        });
        $output->writeln('Order matrix has been synchronized.');

        return static::CODE_SUCCESS;
    }
}
