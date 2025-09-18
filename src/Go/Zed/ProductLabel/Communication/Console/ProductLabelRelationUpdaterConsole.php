<?php

namespace Go\Zed\ProductLabel\Communication\Console;

use Generated\Shared\Transfer\TenantCriteriaTransfer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \Spryker\Zed\ProductLabel\Business\ProductLabelFacadeInterface getFacade()
 * @method \Spryker\Zed\ProductLabel\Persistence\ProductLabelQueryContainerInterface getQueryContainer()
 * @method \Spryker\Zed\ProductLabel\Persistence\ProductLabelRepositoryInterface getRepository()
 */
class ProductLabelRelationUpdaterConsole extends \Spryker\Zed\ProductLabel\Communication\Console\ProductLabelRelationUpdaterConsole
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $locator = (new \Spryker\Zed\Kernel\Container())->getLocator();
        $tenantBehaviorFacade = $locator->tenantBehavior()->facade();
        $tenantOnboardingFacade = $locator->tenantOnboarding()->facade();

        foreach ($tenantOnboardingFacade->getTenants((new TenantCriteriaTransfer()))->getTenants() as $tenant) {
            $tenantBehaviorFacade->setCurrentTenantReference($tenant->getIdentifier());
            $this->getFacade()->updateDynamicProductLabelRelations($this->getMessenger(), $this->isTouchEnabled($input));
        }

        return static::CODE_SUCCESS;
    }

}
