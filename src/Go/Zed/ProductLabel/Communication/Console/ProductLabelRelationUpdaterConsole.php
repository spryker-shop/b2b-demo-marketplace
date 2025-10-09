<?php

namespace Go\Zed\ProductLabel\Communication\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \Spryker\Zed\ProductLabel\Business\ProductLabelFacadeInterface getFacade()
 * @method \Spryker\Zed\ProductLabel\Persistence\ProductLabelQueryContainerInterface getQueryContainer()
 * @method \Spryker\Zed\ProductLabel\Persistence\ProductLabelRepositoryInterface getRepository()
 */
class ProductLabelRelationUpdaterConsole extends \Spryker\Zed\ProductLabel\Communication\Console\ProductLabelRelationUpdaterConsole
{
    use \Go\Zed\TenantBehavior\Communication\Console\TenantIterationTrait;
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $messenger = $this->getMessenger();
        $touchEnabled = $this->isTouchEnabled($input);

        $this->forEachTenant(function () use ($messenger, $touchEnabled): void {
            $this->getFacade()->updateDynamicProductLabelRelations($messenger, $touchEnabled);
        });

        return static::CODE_SUCCESS;
    }

}
