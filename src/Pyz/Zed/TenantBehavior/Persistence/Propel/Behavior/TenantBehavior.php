<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\TenantBehavior\Persistence\Propel\Behavior;

use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Table;
use Propel\Generator\Util\PhpParser;
use Pyz\Zed\TenantBehavior\TenantBehaviorConfig;
use Spryker\Zed\Kernel\BundleConfigResolverAwareTrait;

class TenantBehavior extends Behavior
{
    use BundleConfigResolverAwareTrait;

    /**
     * @var \Pyz\Zed\TenantBehavior\TenantBehaviorConfig|null
     */
    protected $bundleConfig;

    /**
     * @return void
     */
    public function modifyTable(): void
    {
        $table = $this->getTableOrFail();

        if (!$table->hasColumn($this->getConfig()->getTenantIdColumnName())) {
            $this->addTenantIdColumn($table);
        }

        $this->modifyTableIndexes($table);
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    protected function addTenantIdColumn(Table $table): void
    {
        $config = $this->getConfig();
        $columnName = $config->getTenantIdColumnName();

        // Use the same pattern as UuidBehavior
        $table->addColumn([
            'name' => $columnName,
            'type' => PropelTypes::VARCHAR,
            'size' => $config->getTenantIdColumnSize(),
            'required' => true,
            'description' => 'Tenant identifier for multi-tenant isolation',
        ]);
    }

    /**
     * Modify all existing indexes to include the tenant_id column
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    protected function modifyTableIndexes(Table $table): void
    {
        $tenantIdColumnName = $this->getConfig()->getTenantIdColumnName();

        // Check if tenant column exists before modifying indexes
        if (!$table->hasColumn($tenantIdColumnName)) {
            return;
        }

        $indexNamesToSkip = [
            'spy_price_product_concrete_m_r_storage-price_key',
            'spy_price_product_abstract_m_r_storage-price_key'
        ];

        // Get the tenant column object
        $tenantColumn = $table->getColumn($tenantIdColumnName);

        // Modify regular indexes by adding tenant column
        $hasSkippedIndex = false;
        $indexes = $table->getIndices();
        foreach ($indexes as $index) {
            if (in_array($index->getName(), $indexNamesToSkip)) {
                $hasSkippedIndex = true;
                continue;
            }
            if (!$this->indexContainsTenantColumn($index, $tenantIdColumnName)) {
                // Add column object to the index
                $index->addColumn($tenantColumn);
            }
        }

        // Modify unique indexes by adding tenant column
        $uniqueIndexes = $table->getUnices();
        foreach ($uniqueIndexes as $uniqueIndex) {
            if (in_array($uniqueIndex->getName(), $indexNamesToSkip)) {
                $hasSkippedIndex = true;
                continue;
            }
            if (!$this->indexContainsTenantColumn($uniqueIndex, $tenantIdColumnName)) {
                // Add column object to the unique index
                $uniqueIndex->addColumn($tenantColumn);
            }
        }

        if ($hasSkippedIndex) {
            $index = new Index();
            $index->setName($table->getName() . '_tenant_behavior_index');
            $index->addColumn($tenantColumn);
            $table->addIndex(
                $index
            );
        }
    }

    /**
     * Check if an index already contains the tenant column
     *
     * @param \Propel\Generator\Model\Index|\Propel\Generator\Model\Unique $index
     * @param string $tenantIdColumnName
     *
     * @return bool
     */
    protected function indexContainsTenantColumn($index, string $tenantIdColumnName): bool
    {
        // Try to get the column names from the index
        try {
            $hasColumn = $index->hasColumn($tenantIdColumnName);
            return $hasColumn;
        } catch (\Exception $e) {
            // Fallback: check manually if hasColumn method doesn't exist
            $columns = $index->getColumns();
            foreach ($columns as $column) {
                // Handle both string and object cases
                $columnName = is_string($column) ? $column : (method_exists($column, 'getName') ? $column->getName() : '');
                if ($columnName === $tenantIdColumnName) {
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * @return string
     */
    public function preSave(): string
    {
        return "
\$this->setTenantIdFromContainer();
        ";
    }

    /**
     * @return string
     */
    public function preSelectQuery(): string
    {
        return "
\$this->applyTenantFilter();"
        ;
    }


    /**
     * @return string
     */
    public function preUpdate(): string
    {
        return "
\$this->setTenantIdFromContainer();
        ";
    }

    /**
     * @return string
     */
    public function preInsert(): string
    {
        return "
\$this->setTenantIdFromContainer();
        ";
    }

    /**
     * @return string
     */
    public function preDelete(): string
    {
        return "
\$this->setTenantIdFromContainer();
        ";
    }

    /**
     * @return string
     */
    public function objectAttributes(): string
    {
        return "
/**
 * @var int|null
 */
protected \$_tenantId;
        ";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    public function objectFilter(&$script): void
    {
        $parser = new PhpParser($script, true);
        $this->addTenantIdInitialization($parser);
        $script = $parser->getCode();
    }

    /**
     * @param \Propel\Generator\Util\PhpParser $parser
     *
     * @return void
     */
    protected function addTenantIdInitialization(PhpParser $parser): void
    {
        $tenantIdColumnName = $this->getConfig()->getTenantIdColumnName();
        $setterMethodName = 'set' . $this->camelize($tenantIdColumnName);

        $methodNamePattern = '(' . $setterMethodName . '\(\$v\)\n[ ]*{)';
        $newMethodCode = $parser->findMethod($setterMethodName);

        if ($newMethodCode) {
            $newMethodCode = (string)preg_replace_callback($methodNamePattern, function ($matches) use ($tenantIdColumnName) {
                return $matches[0] . "\n\t\t\$this->_tenantId = \$this->$tenantIdColumnName;\n";
            }, $newMethodCode);

            $parser->replaceMethod($setterMethodName, $newMethodCode);
        }
    }

    /**
     * @return string
     */
    public function objectMethods(): string
    {
        $tenantIdColumnName = $this->getConfig()->getTenantIdColumnName();

        return "
/**
 * @return void
 */
protected function setTenantIdFromContainer(): void
{
    if (\$this->$tenantIdColumnName) {
        return;
    }

    \$idTenant = \$this->getTenantIdFromServiceContainer();
    if (\$idTenant !== null) {
        \$this->" . $this->getColumnSetter($tenantIdColumnName) . "(\$idTenant);
    }
}

/**
 * @return string|null
 */
protected function getTenantIdFromServiceContainer(): ?string
{
    return \\Spryker\\Zed\\Kernel\\Locator::getInstance()->tenantBehavior()->facade()->getCurrentTenantId();
}
        ";
    }

    /**
     * @return string
     */
    public function queryMethods(): string
    {
        return "
/**
 * @return \$this
 */
public function applyTenantFilter()
{
    \$idTenant = \$this->getTenantIdFromServiceContainer();

    if (\$idTenant !== null) {
        return \$this->filterByIdTenant(\$idTenant);
    }

    return \$this;
}

/**
 * @return string|null
 */
protected function getTenantIdFromServiceContainer(): ?string
{
    return \\Spryker\\Zed\\Kernel\\Locator::getInstance()->tenantBehavior()->facade()->getCurrentTenantId();
}
        ";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    public function queryFilter(&$script): void
    {
        // Auto-apply tenant filtering to find* methods
        $script = $this->addAutoTenantFiltering($script);
    }

    /**
     * @param string $script
     *
     * @return string
     */
    protected function addAutoTenantFiltering(string $script): string
    {
        // Add tenant filtering to common find methods
        $findMethods = ['find', 'findOne', 'findByPK', 'findPk', 'count'];

        foreach ($findMethods as $method) {
            $pattern = '/public function ' . $method . '\([^{]*\{/';
            $replacement = function ($matches) {
                return $matches[0] . "\n        \$this->applyTenantFilter();";
            };
            $script = preg_replace_callback($pattern, $replacement, $script);
        }

        // Re-enable tenant filtering for delete methods now that import works
        $deleteMethods = ['delete', 'deleteAll'];
        foreach ($deleteMethods as $method) {
            $pattern = '/public function ' . $method . '\([^{]*\{/';
            $replacement = function ($matches) {
                return $matches[0] . "\n        \$this->applyTenantFilter();";
            };
            $script = preg_replace_callback($pattern, $replacement, $script);
        }

        return $script;
    }

    /**
     * @return string
     */
    protected function getTableMapClassName(): string
    {
        $table = $this->getTable();
        if (!$table) {
            return '';
        }

        return $table->getPhpName() . 'TableMap';
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function camelize(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }

    /**
     * @return \Pyz\Zed\TenantBehavior\TenantBehaviorConfig
     */
    protected function getConfig(): TenantBehaviorConfig
    {
        if (!isset($this->bundleConfig)) {
            $this->bundleConfig = $this->resolveBundleConfig();
        }

        return $this->bundleConfig;
    }

    protected function getColumnSetter(string $column): string
    {
        return 'set' . $this->table->getColumn($column)->getPhpName();
    }
}
