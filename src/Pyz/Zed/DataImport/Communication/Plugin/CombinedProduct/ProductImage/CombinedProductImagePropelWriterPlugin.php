<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\DataImport\Communication\Plugin\CombinedProduct\ProductImage;

use Generated\Shared\Transfer\DataSetItemTransfer;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSet;
use Spryker\Zed\DataImportExtension\Dependency\Plugin\DataSetItemWriterPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Pyz\Zed\DataImport\Business\DataImportFacadeInterface getFacade()
 * @method \Pyz\Zed\DataImport\DataImportConfig getConfig()
 * @method \Spryker\Zed\DataImport\Communication\DataImportCommunicationFactory getFactory()
 */
class CombinedProductImagePropelWriterPlugin extends AbstractPlugin implements DataSetItemWriterPluginInterface
{
    public function write(DataSetItemTransfer $dataSetItemTransfer): void
    {
        $this->getFacade()->writeCombinedProductImageDataSet(new DataSet($dataSetItemTransfer->getPayload()));
    }

    public function flush(): void
    {
        $this->getFacade()->flushCombinedProductImageDataImporter();
    }
}
