<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Go\Zed\TenantOnboarding\Communication\Table;

use Go\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface;
use Go\Zed\TenantOnboarding\TenantOnboardingConfig;
use Orm\Zed\TenantOnboarding\Persistence\Map\PyzTenantRegistrationTableMap;
use Orm\Zed\TenantOnboarding\Persistence\PyzTenantRegistrationQuery;
use Spryker\Service\UtilText\Model\Url\Url;
use Spryker\Zed\Gui\Communication\Table\AbstractTable;
use Spryker\Zed\Gui\Communication\Table\TableConfiguration;

class TenantRegistrationTable extends AbstractTable
{
    public const COL_ID = 'id_tenant_registration';
    public const COL_COMPANY_NAME = 'company_name';
    public const COL_TENANT_NAME = 'tenant_name';
    public const COL_EMAIL = 'email';
    public const COL_STATUS = 'status';
    public const COL_CREATED_AT = 'created_at';
    public const COL_ACTIONS = 'actions';

    /**
     * @var \Go\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface
     */
    protected TenantOnboardingFacadeInterface $tenantOnboardingFacade;

    /**
     * @param \Go\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface $tenantOnboardingFacade
     */
    public function __construct(TenantOnboardingFacadeInterface $tenantOnboardingFacade)
    {
        $this->tenantOnboardingFacade = $tenantOnboardingFacade;
    }

    /**
     * @param \Spryker\Zed\Gui\Communication\Table\TableConfiguration $config
     *
     * @return \Spryker\Zed\Gui\Communication\Table\TableConfiguration
     */
    protected function configure(TableConfiguration $config): TableConfiguration
    {
        $config->setHeader([
            static::COL_ID => 'ID',
            static::COL_COMPANY_NAME => 'Company Name',
            static::COL_TENANT_NAME => 'Tenant Identifier',
            static::COL_EMAIL => 'Email',
            static::COL_STATUS => 'Status',
            static::COL_CREATED_AT => 'Created At',
            static::COL_ACTIONS => 'Actions',
        ]);

        $config->setSortable([
            static::COL_ID,
            static::COL_COMPANY_NAME,
            static::COL_TENANT_NAME,
            static::COL_EMAIL,
            static::COL_STATUS,
            static::COL_CREATED_AT,
        ]);

        $config->setSearchable([
            static::COL_COMPANY_NAME,
            static::COL_TENANT_NAME,
            static::COL_EMAIL,
        ]);

        $config->addRawColumn(static::COL_ACTIONS);
        $config->addRawColumn(static::COL_STATUS);

        $config->setDefaultSortField(static::COL_CREATED_AT, TableConfiguration::SORT_DESC);

        return $config;
    }

    /**
     * @param \Spryker\Zed\Gui\Communication\Table\TableConfiguration $config
     *
     * @return array
     */
    protected function prepareData(TableConfiguration $config): array
    {
        $query = PyzTenantRegistrationQuery::create();
        $queryResults = $this->runQuery($query, $config);

        $results = [];
        foreach ($queryResults as $queryResult) {
            $results[] = [
                static::COL_ID => $queryResult[PyzTenantRegistrationTableMap::COL_ID_TENANT_REGISTRATION],
                static::COL_TENANT_NAME => $queryResult[PyzTenantRegistrationTableMap::COL_TENANT_NAME],
                static::COL_EMAIL => $queryResult[PyzTenantRegistrationTableMap::COL_EMAIL],
                static::COL_COMPANY_NAME => $queryResult[PyzTenantRegistrationTableMap::COL_COMPANY_NAME],
                static::COL_STATUS => $this->formatStatusBadge($queryResult[PyzTenantRegistrationTableMap::COL_STATUS]),
                static::COL_CREATED_AT => $queryResult[PyzTenantRegistrationTableMap::COL_CREATED_AT],
                static::COL_ACTIONS => $this->generateActionButtons($queryResult[PyzTenantRegistrationTableMap::COL_ID_TENANT_REGISTRATION], $queryResult[PyzTenantRegistrationTableMap::COL_STATUS]),
            ];
        }

        return $results;
    }

    /**
     * @param string $status
     *
     * @return string
     */
    protected function formatStatusBadge(string $status): string
    {
        $statusLabels = [
            TenantOnboardingConfig::REGISTRATION_STATUS_PENDING => 'Pending',
            TenantOnboardingConfig::REGISTRATION_STATUS_APPROVED => 'Approved',
            TenantOnboardingConfig::REGISTRATION_STATUS_DECLINED => 'Declined',
            TenantOnboardingConfig::REGISTRATION_STATUS_FAILED => 'Failed',
            TenantOnboardingConfig::REGISTRATION_STATUS_PROCESSING=> 'Processing',
            TenantOnboardingConfig::REGISTRATION_STATUS_COMPLETED=> 'Completed',
        ];
        $statusClasses = [
            TenantOnboardingConfig::REGISTRATION_STATUS_PENDING => 'info',
            TenantOnboardingConfig::REGISTRATION_STATUS_APPROVED => 'primary',
            TenantOnboardingConfig::REGISTRATION_STATUS_DECLINED => 'warning',
            TenantOnboardingConfig::REGISTRATION_STATUS_FAILED => 'danger',
            TenantOnboardingConfig::REGISTRATION_STATUS_PROCESSING => 'primary',
            TenantOnboardingConfig::REGISTRATION_STATUS_COMPLETED => 'success',
        ];

        $badgeClass = $statusClasses[$status] ?? 'primary';
        $label = $statusLabels[$status] ?? 'Unknown';

        return sprintf('<span class="badge bg-%s">%s</span>', $badgeClass, $label);
    }

    /**
     * @param int|null $idTenantRegistration
     * @param string $status
     *
     * @return string
     */
    protected function generateActionButtons(?int $idTenantRegistration, string $status): string
    {
        $buttons = [];

        if ($status === TenantOnboardingConfig::REGISTRATION_STATUS_PENDING) {
            $approveUrl = Url::generate('/tenant-onboarding/index/approve', ['id' => $idTenantRegistration]);
            $declineUrl = Url::generate('/tenant-onboarding/index/decline', ['id' => $idTenantRegistration]);

            $buttons[] = sprintf(
                '<a href="%s" class="btn btn-sm btn-success" onclick="return confirm(\'Are you sure you want to approve this registration?\')">Approve</a>',
                $approveUrl
            );

            $buttons[] = sprintf(
                '<a href="#" class="btn btn-sm btn-danger" onclick="return declineRegistration(%d);">Decline</a>',
                $idTenantRegistration
            );
        }

        return implode(' ', $buttons);
    }
}
