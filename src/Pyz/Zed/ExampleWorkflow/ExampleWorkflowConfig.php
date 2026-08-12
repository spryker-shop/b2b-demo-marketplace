<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ExampleWorkflow;

use Spryker\Zed\Kernel\AbstractBundleConfig;

class ExampleWorkflowConfig extends AbstractBundleConfig
{
    /**
     * The B2B CompanyOnboarding demo starts when a company is created.
     *
     * @var string
     */
    public const EVENT_COMPANY_CREATE = 'Entity.spy_company.create';

    /**
     * @var string
     */
    public const SUBJECT_TYPE_COMPANY = 'Company';
}
