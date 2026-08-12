<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\Workflow;

use Generated\Shared\Transfer\DataImporterDataSourceConfigurationTransfer;
use Spryker\Zed\Workflow\WorkflowConfig as SprykerWorkflowConfig;

class WorkflowConfig extends SprykerWorkflowConfig
{
    /**
     * @var string
     */
    protected const MODULE_NAME_WORKFLOW = 'workflow';

    /**
     * {@inheritDoc}
     *
     * The core config sets the module name to `Workflow`, which the data-import file resolver turns into
     * `vendor/spryker/Workflow/data/import/workflow.csv`. Composer installs the module under the lowercase
     * `vendor/spryker/workflow`, so on a case-sensitive filesystem that path does not exist and the import
     * aborts before the YAML `source` override is applied. Overriding the module name with the lowercase,
     * dash-cased directory name makes the default path resolve.
     *
     * @api
     */
    public function getWorkflowDataImporterDataSourceConfiguration(): DataImporterDataSourceConfigurationTransfer
    {
        return parent::getWorkflowDataImporterDataSourceConfiguration()
            ->setModuleName(static::MODULE_NAME_WORKFLOW);
    }
}
