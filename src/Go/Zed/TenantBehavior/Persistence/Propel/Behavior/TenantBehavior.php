<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\TenantBehavior\Persistence\Propel\Behavior;

use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Table;
use Propel\Generator\Util\PhpParser;
use Go\Zed\TenantBehavior\TenantBehaviorConfig;
use Spryker\Zed\Kernel\BundleConfigResolverAwareTrait;

class TenantBehavior extends Behavior
{
    use BundleConfigResolverAwareTrait;

    /**
     * @var \Go\Zed\TenantBehavior\TenantBehaviorConfig|null
     */
    protected $bundleConfig;

    /**
     * @return void
     */
    public function modifyTable(): void
    {
        $table = $this->getTableOrFail();

        if (!$table->hasColumn($this->getConfig()->getTenantReferenceColumnName())) {
            $this->addTenantReferenceColumn($table);
        }

        $this->modifyTableIndexes($table);
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    protected function addTenantReferenceColumn(Table $table): void
    {
        $config = $this->getConfig();
        $columnName = $config->getTenantReferenceColumnName();

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
        $tenantReferenceColumnName = $this->getConfig()->getTenantReferenceColumnName();

        // Check if tenant column exists before modifying indexes
        if (!$table->hasColumn($tenantReferenceColumnName)) {
            return;
        }

        $indexNamesToSkip = [
        ];

        // Get the tenant column object
        $tenantColumn = $table->getColumn($tenantReferenceColumnName);

        // Modify regular indexes by adding tenant column
        $hasSkippedIndex = false;
        $indexes = $table->getIndices();
        foreach ($indexes as $index) {
            if (in_array($index->getName(), $indexNamesToSkip)) {
                $hasSkippedIndex = true;
                continue;
            }
            if (!$this->indexContainsTenantColumn($index, $tenantReferenceColumnName)) {
                // Add column object to the index
                $index->addColumn(['name' => $tenantReferenceColumnName]);
            }
        }

        // Modify unique indexes by adding tenant column
        $uniqueIndexes = $table->getUnices();
        foreach ($uniqueIndexes as $uniqueIndex) {
            if (in_array($uniqueIndex->getName(), $indexNamesToSkip)) {
                $hasSkippedIndex = true;
                continue;
            }
            if (!$this->indexContainsTenantColumn($uniqueIndex, $tenantReferenceColumnName)) {
                // Add column object to the unique index
                $uniqueIndex->addColumn(['name' => $tenantReferenceColumnName]);
            }
        }

        if ($hasSkippedIndex) {
            $index = new Index();
            $index->setName($table->getName() . '_tenant_behavior_index');
            $index->addColumn(['name' => $tenantReferenceColumnName]);
            $table->addIndex(
                $index
            );
        }
    }

    /**
     * Check if an index already contains the tenant column
     *
     * @param \Propel\Generator\Model\Index|\Propel\Generator\Model\Unique $index
     * @param string $tenantReferenceColumnName
     *
     * @return bool
     */
    protected function indexContainsTenantColumn($index, string $tenantReferenceColumnName): bool
    {
        // Try to get the column names from the index
        try {
            $hasColumn = $index->hasColumn($tenantReferenceColumnName);
            return $hasColumn;
        } catch (\Exception $e) {
            // Fallback: check manually if hasColumn method doesn't exist
            $columns = $index->getColumns();
            foreach ($columns as $column) {
                // Handle both string and object cases
                $columnName = is_string($column) ? $column : (method_exists($column, 'getName') ? $column->getName() : '');
                if ($columnName === $tenantReferenceColumnName) {
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
\$this->setTenantReferenceFromContainer();
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
\$this->setTenantReferenceFromContainer();
        ";
    }

    /**
     * @return string
     */
    public function preInsert(): string
    {
        return "
\$this->setTenantReferenceFromContainer();
        ";
    }

    /**
     * @return string
     */
    public function preDelete(): string
    {
        return "
\$this->setTenantReferenceFromContainer();
        ";
    }

    /**
     * @return string
     */
    public function objectAttributes(): string
    {
        return "
/**
 * @var string|null
 */
protected \$_tenantReference = null;
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
        $tenantReferenceColumnName = $this->getConfig()->getTenantReferenceColumnName();
        $setterMethodName = 'set' . $this->camelize($tenantReferenceColumnName);

        $methodNamePattern = '(' . $setterMethodName . '\(\$v\)\n[ ]*{)';
        $newMethodCode = $parser->findMethod($setterMethodName);

        if ($newMethodCode) {
            $newMethodCode = (string)preg_replace_callback($methodNamePattern, function ($matches) use ($tenantReferenceColumnName) {
                return $matches[0] . "\n\t\t\$this->_tenantReference = \$this->$tenantReferenceColumnName;\n";
            }, $newMethodCode);

            $parser->replaceMethod($setterMethodName, $newMethodCode);
        }
    }

    /**
     * @return string
     */
    public function objectMethods(): string
    {
        $tenantReferenceColumnName = $this->getConfig()->getTenantReferenceColumnName();

        return "
/**
 * @return void
 */
public function setTenantReferenceFromContainer(): void
{
    if (\$this->$tenantReferenceColumnName) {
        return;
    }

    \$tenantReference = \$this->getTenantReferenceFromServiceContainer();
    if (\$tenantReference !== null) {
        \$this->" . $this->getColumnSetter($tenantReferenceColumnName) . "(\$tenantReference);
    }
}

/**
 * @return string|null
 */
protected function getTenantReferenceFromServiceContainer(): ?string
{
    return \\Spryker\\Zed\\Kernel\\Locator::getInstance()->tenantBehavior()->facade()->getCurrentTenantReference();
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
    \$tenantReference = \$this->getTenantReferenceFromServiceContainer();

    if (\$tenantReference !== null) {
        return \$this->filterByTenantReference(\$tenantReference);
    }

    return \$this;
}

/**
 * @return string|null
 */
protected function getTenantReferenceFromServiceContainer(): ?string
{
    return \\Spryker\\Zed\\Kernel\\Locator::getInstance()->tenantBehavior()->facade()->getCurrentTenantReference();
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
     * @return \Go\Zed\TenantBehavior\TenantBehaviorConfig
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
