<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\ProductManagement\Communication;

use Demo\Zed\ProductManagement\Communication\Form\ProductConcreteFormEdit;
use Demo\Zed\ProductManagement\Communication\Form\ProductFormAdd;
use Demo\Zed\ProductManagement\Communication\Form\ProductFormEdit;
use Demo\Zed\ProductManagement\Dependency\Facade\ProductManagementToPriceInterface;
use Demo\Zed\ProductManagement\ProductManagementDependencyProvider;
use Spryker\Zed\ProductManagement\Communication\ProductManagementCommunicationFactory as SprykerProductManagementCommunicationFactory;
use Symfony\Component\Form\FormInterface;

/**
 * @method \Pyz\Zed\ProductManagement\ProductManagementConfig getConfig()
 * @method \Spryker\Zed\ProductManagement\Persistence\ProductManagementRepositoryInterface getRepository()
 * @method \Spryker\Zed\ProductManagement\Business\ProductManagementFacadeInterface getFacade()
 */
class ProductManagementCommunicationFactory extends SprykerProductManagementCommunicationFactory
{
    /**
     * @param array<string, mixed> $formData
     * @param array<string, mixed> $formOptions
     */
    public function createProductFormAdd(array $formData, array $formOptions = []): FormInterface
    {
        return $this->getFormFactory()->create(ProductFormAdd::class, $formData, $formOptions);
    }

    /**
     * @param array<string, mixed> $formData
     * @param array<string, mixed> $formOptions
     */
    public function createProductFormEdit(array $formData, array $formOptions = []): FormInterface
    {
        return $this->getFormFactory()->create(ProductFormEdit::class, $formData, $formOptions);
    }

    /**
     * @param array<string, mixed> $formData
     * @param array<string, mixed> $formOptions
     */
    public function createProductVariantFormEdit(array $formData, array $formOptions = []): FormInterface
    {
        return $this->getFormFactory()->create(ProductConcreteFormEdit::class, $formData, $formOptions);
    }

    public function getPriceFacade(): ProductManagementToPriceInterface
    {
        return $this->getProvidedDependency(ProductManagementDependencyProvider::FACADE_PRICE);
    }
}
