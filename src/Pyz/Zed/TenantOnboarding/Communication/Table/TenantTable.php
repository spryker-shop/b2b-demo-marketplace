<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Communication\Table;

use Generated\Shared\Transfer\TenantCriteriaTransfer;
use Orm\Zed\TenantOnboarding\Persistence\PyzTenant;
use Orm\Zed\TenantOnboarding\Persistence\PyzTenantQuery;
use Spryker\Service\UtilText\Model\Url\Url;
use Spryker\Zed\Gui\Communication\Table\AbstractTable;
use Spryker\Zed\Gui\Communication\Table\TableConfiguration;

class TenantTable extends AbstractTable
{
    protected const COL_ID_TENANT = 'id_tenant';
    protected const COL_IDENTIFIER = 'identifier';
    protected const COL_TENANT_HOST = 'tenant_host';
    protected const COL_CREATED_AT = 'created_at';
    protected const COL_ACTIONS = 'actions';

    /**
     * @param \Orm\Zed\TenantOnboarding\Persistence\PyzTenantQuery $tenantQuery
     */
    public function __construct(
        protected PyzTenantQuery $tenantQuery
    ) {
    }

    /**
     * @param \Spryker\Zed\Gui\Communication\Table\TableConfiguration $config
     *
     * @return \Spryker\Zed\Gui\Communication\Table\TableConfiguration
     */
    protected function configure(TableConfiguration $config): TableConfiguration
    {
        $config->setHeader([
            static::COL_ID_TENANT => 'ID',
            static::COL_IDENTIFIER => 'Identifier',
            static::COL_TENANT_HOST => 'Host',
            static::COL_CREATED_AT => 'Created',
            static::COL_ACTIONS => 'Actions',
        ]);

        $config->setSortable([
            static::COL_ID_TENANT,
            static::COL_IDENTIFIER,
            static::COL_TENANT_HOST,
            static::COL_CREATED_AT,
        ]);

        $config->setSearchable([
            static::COL_IDENTIFIER,
            static::COL_TENANT_HOST,
        ]);
        $config->addRawColumn(static::COL_ACTIONS);

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
        $query = $this->tenantQuery;
        $queryResults = $this->runQuery($query, $config, true);

        $utilDataTimeService = (new \Spryker\Service\UtilDateTime\UtilDateTimeService());
        $results = [];
        foreach ($queryResults as $tenantEntity) {
            /** @var \Orm\Zed\TenantOnboarding\Persistence\PyzTenant $tenantEntity */
            $results[] = [
                static::COL_ID_TENANT => $tenantEntity->getIdTenant(),
                static::COL_IDENTIFIER => $tenantEntity->getIdentifier(),
                static::COL_TENANT_HOST => $tenantEntity->getTenantHost(),
                static::COL_CREATED_AT => $utilDataTimeService->formatDateTime($tenantEntity->getCreatedAt()),
                static::COL_ACTIONS => $this->buildLinks($tenantEntity),
            ];
        }

        return $results;
    }

    /**
     * @param \Orm\Zed\TenantOnboarding\Persistence\PyzTenant $tenantEntity
     *
     * @return string
     */
    protected function buildLinks(\Orm\Zed\TenantOnboarding\Persistence\PyzTenant $tenantEntity): string
    {
        $buttons = [];

        $buttons[] = $this->generateViewButton(
            Url::generate('/tenant-onboarding/tenant/view', [
                'id-tenant' => $tenantEntity->getIdTenant(),
            ]),
            'View'
        );

        return implode(' ', $buttons);
    }
}
