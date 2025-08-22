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

        $results = [];
        foreach ($queryResults as $tenantEntity) {
            $results[] = [
                static::COL_ID_TENANT => $tenantEntity['id_tenant'],
                static::COL_IDENTIFIER => $tenantEntity['identifier'],
                static::COL_TENANT_HOST => $tenantEntity['tenant_host'],
                static::COL_CREATED_AT => $tenantEntity['created_at'],
                static::COL_ACTIONS => $this->buildLinks($tenantEntity),
            ];
        }

        return $results;
    }

    /**
     * @param array $tenantEntity
     *
     * @return string
     */
    protected function buildLinks(array $tenantEntity): string
    {
        $buttons = [];

        $buttons[] = $this->generateViewButton(
            Url::generate('/tenant-onboarding/tenant/view', [
                'id-tenant' => $tenantEntity['id_tenant'],
            ]),
            'View'
        );

        return implode(' ', $buttons);
    }
}