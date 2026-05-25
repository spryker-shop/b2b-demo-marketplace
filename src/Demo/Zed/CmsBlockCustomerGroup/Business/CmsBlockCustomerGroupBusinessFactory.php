<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockCustomerGroup\Business;

use Demo\Zed\CmsBlockCustomerGroup\Business\Checker\CmsBlockCustomerGroupChecker;
use Demo\Zed\CmsBlockCustomerGroup\Business\Checker\CmsBlockCustomerGroupCheckerInterface;
use Demo\Zed\CmsBlockCustomerGroup\Business\Validator\CmsBlockCustomerValidator;
use Demo\Zed\CmsBlockCustomerGroup\Business\Validator\CmsBlockValidator;
use Demo\Zed\CmsBlockCustomerGroup\Business\Validator\CmsBlockValidatorInterface;
use Demo\Zed\CmsBlockCustomerGroup\Business\Writer\CmsBlockCustomerGroupWriter;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;

/**
 * @method \Demo\Zed\CmsBlockCustomerGroup\Persistence\CmsBlockCustomerGroupRepositoryInterface getRepository()
 * @method \Demo\Zed\CmsBlockCustomerGroup\Persistence\CmsBlockCustomerGroupEntityManagerInterface getEntityManager()
 */
class CmsBlockCustomerGroupBusinessFactory extends AbstractBusinessFactory
{
    public function createCmsBlockCustomerGroupWriter(): CmsBlockCustomerGroupWriter
    {
        return new CmsBlockCustomerGroupWriter(
            $this->getEntityManager(),
            $this->getRepository(),
        );
    }

    public function createCmsBlockCustomerGroupChecker(): CmsBlockCustomerGroupCheckerInterface
    {
        return new CmsBlockCustomerGroupChecker($this->getRepository());
    }

    public function createCmsBlockCustomerValidator(): CmsBlockValidatorInterface
    {
        return new CmsBlockCustomerValidator($this->createCmsBlockCustomerGroupChecker());
    }

    /**
     * @return array<\Demo\Zed\CmsBlockCustomerGroup\Business\Validator\CmsBlockValidatorInterface>
     */
    public function getCmsBlockValidators(): array
    {
        return [
            $this->createCmsBlockCustomerValidator(),
        ];
    }

    public function createCmsBlockValidator(): CmsBlockValidatorInterface
    {
        return new CmsBlockValidator($this->getCmsBlockValidators());
    }
}
