<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\EventBehavior\Persistence\Propel\Behavior;

use Laminas\Filter\Word\UnderscoreToCamelCase;
use LogicException;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Table;
use Propel\Generator\Util\PhpParser;
use Propel\Runtime\Exception\PropelException;
use Spryker\Zed\Kernel\BundleConfigResolverAwareTrait;

/**
 * @method \Spryker\Zed\EventBehavior\EventBehaviorConfig getConfig()
 */
class EventBehavior extends Behavior
{
    use BundleConfigResolverAwareTrait;

    /**
     * @var string
     */
    public const EVENT_CHANGE_ENTITY_NAME = 'name';

    /**
     * @var string
     */
    public const EVENT_CHANGE_ENTITY_ID = 'id';

    /**
     * @var string
     */
    public const EVENT_CHANGE_ENTITY_FOREIGN_KEYS = 'foreignKeys';

    /**
     * @var string
     */
    public const EVENT_CHANGE_ENTITY_MODIFIED_COLUMNS = 'modifiedColumns';

    /**
     * @var string
     */
    public const EVENT_CHANGE_ENTITY_ORIGINAL_VALUES = 'originalValues';

    /**
     * @var string
     */
    public const EVENT_CHANGE_ENTITY_ADDITIONAL_VALUES = 'additionalValues';

    /**
     * @var string
     */
    public const EVENT_CHANGE_NAME = 'event';

    /**
     * @return string
     */
    public function preSave()
    {
        return "
\$this->prepareSaveEventName();
        ";
    }

    /**
     * @return string
     */
    public function postSave()
    {
        return "
if (\$affectedRows) {
    \$this->addSaveEventToMemory();
}
        ";
    }

    /**
     * @return string
     */
    public function postDelete()
    {
        return "
\$this->addDeleteEventToMemory();
        ";
    }

    /**
     * Adds a single parameter.
     *
     * Expects an associative array looking like
     * [ 'name' => 'foo', 'value' => bar ]
     *
     * @param array $parameter
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return void
     */
    public function addParameter(array $parameter): void
    {
        $parameter = array_change_key_case($parameter, CASE_LOWER);

        $this->parameters[$parameter['name']] = [];

        if (!isset($parameter['column'])) {
            throw new PropelException(sprintf('"column" attribute for %s event behavior is missing', $parameter['name']));
        }

        $this->parameters[$parameter['name']]['column'] = $parameter['column'];

        if (isset($parameter['value'])) {
            $this->parameters[$parameter['name']]['value'] = $parameter['value'];
        }

        if (isset($parameter['operator'])) {
            $this->parameters[$parameter['name']]['operator'] = $parameter['operator'];
        }

        if (isset($parameter['keep-original'])) {
            $this->parameters[$parameter['name']]['keep-original'] = $parameter['keep-original'];
        }

        if (isset($parameter['keep-additional'])) {
            $this->parameters[$parameter['name']]['keep-additional'] = $parameter['keep-additional'];
        }
    }

    /**
     * @return string
     */
    public function objectAttributes()
    {
        $script = '';
        $script .= $this->addEventAttributes();
        $script .= $this->addForeignKeysAttribute();

        return $script;
    }

    /**
     * @param string $script
     *
     * @return void
     */
    public function objectFilter(&$script)
    {
        $parser = new PhpParser($script, true);
        $eventColumns = $this->getParameters();

        foreach ($eventColumns as $eventColumn) {
            if ($eventColumn['column'] === '*') {
                continue;
            }
            $this->addSetInitialValueStatement($parser, $eventColumn['column']);
        }

        $script = $parser->getCode();
    }

    /**
     * @return string
     */
    public function objectMethods()
    {
        $script = '';
        $script .= $this->addPrepareEventMethod();
        $script .= $this->addToggleEventMethod();
        $script .= $this->addSaveEventMethod();
        $script .= $this->addDeleteEventMethod();
        $script .= $this->addGetForeignKeysMethod();
        $script .= $this->addSaveEventBehaviorEntityChangeMethod();
        $script .= $this->addIsEventColumnsModifiedMethod();
        $script .= $this->addGetOriginalValuesMethod();
        $script .= $this->addGetAdditionalValuesMethod();
        $script .= $this->addGetPhpType();

        return $script;
    }

    /**
     * @param \Propel\Generator\Util\PhpParser $parser
     * @param string $column
     *
     * @return void
     */
    protected function addSetInitialValueStatement(PhpParser $parser, string $column)
    {
        $camelCaseFilter = new UnderscoreToCamelCase();

        /** @var string $name */
        $name = $camelCaseFilter->filter($column);
        $methodName = sprintf('set%s', $name);
        $initialValueField = sprintf('[%sTableMap::COL_%s]', $this->getTableOrFail()->getPhpName(), strtoupper($column));

        $methodNamePattern = '(' . $methodName . '\(\$v\)\n[ ]*{)';
        $newMethodCode = (string)preg_replace_callback($methodNamePattern, function ($matches) use ($initialValueField, $column) {
            return $matches[0] . "\n\t\t\$this->_initialValues$initialValueField = \$this->$column;\n";
        }, (string)$parser->findMethod($methodName));

        $parser->replaceMethod($methodName, $newMethodCode);
    }

    /**
     * @return string
     */
    protected function addEventAttributes()
    {
        return "
/**
 * @var string
 */
private \$_eventName;

/**
 * @var bool
 */
private \$_isModified;

/**
 * @var array
 */
private \$_modifiedColumns;

/**
 * @var array
 */
private \$_initialValues;

/**
 * @var bool
 */
private \$_isEventDisabled;
        ";
    }

    /**
     * @return string
     */
    protected function addForeignKeysAttribute()
    {
        $foreignKeys = $this->getTableOrFail()->getForeignKeys();
        $tableName = $this->getTableOrFail()->getName();
        $implodedForeignKeys = '';

        foreach ($foreignKeys as $foreignKey) {
            $fullColumnName = sprintf('%s.%s', $tableName, $foreignKey->getLocalColumnName());
            $implodedForeignKeys .= sprintf("
    '%s' => '%s',", $fullColumnName, $foreignKey->getLocalColumnName());
        }

        return "
/**
 * @var array
 */
private \$_foreignKeys = [$implodedForeignKeys
];
        ";
    }

    /**
     * @return string
     */
    protected function addPrepareEventMethod()
    {
        $createEvent = 'Entity.' . $this->getTableOrFail()->getName() . '.create';
        $updateEvent = 'Entity.' . $this->getTableOrFail()->getName() . '.update';

        return "
/**
 * @return void
 */
protected function prepareSaveEventName()
{
    if (\$this->isNew()) {
        \$this->_eventName = '$createEvent';
    } else {
        \$this->_eventName = '$updateEvent';
    }

    \$this->_modifiedColumns = \$this->getModifiedColumns();
    \$this->_isModified = \$this->isModified();
}
        ";
    }

    /**
     * @return string
     */
    protected function addToggleEventMethod()
    {
        return "
/**
 * @return void
 */
public function disableEvent()
{
    \$this->_isEventDisabled = true;
}

/**
 * @return void
 */
public function enableEvent()
{
    \$this->_isEventDisabled = false;
}
        ";
    }

    /**
     * @return string
     */
    protected function addSaveEventMethod()
    {
        $tableName = $this->getTableOrFail()->getName();
        $dataEventEntityName = static::EVENT_CHANGE_ENTITY_NAME;
        $dataEventEntityId = static::EVENT_CHANGE_ENTITY_ID;
        $dataEventEntityForeignKeys = static::EVENT_CHANGE_ENTITY_FOREIGN_KEYS;
        $dataEventEntityModifiedColumns = static::EVENT_CHANGE_ENTITY_MODIFIED_COLUMNS;
        $dataEventEntityOriginalValues = static::EVENT_CHANGE_ENTITY_ORIGINAL_VALUES;
        $dataEventEntityAdditionalValues = static::EVENT_CHANGE_ENTITY_ADDITIONAL_VALUES;
        $dataEventName = static::EVENT_CHANGE_NAME;

        $tenantBehavior = '';
        if ($this->table->hasBehavior('\Pyz\Zed\TenantBehavior\Persistence\Propel\Behavior\TenantBehavior')) {
            $tenantBehavior = '$data[\'id_tenant\'] = $this->id_tenant;';
        }

        return "
/**
 * @return void
 */
protected function addSaveEventToMemory()
{
    if (\$this->_isEventDisabled) {
        return;
    }

    if (\$this->_eventName !== 'Entity.$tableName.create') {
        if (!\$this->_isModified) {
            return;
        }

        if (!\$this->isEventColumnsModified()) {
            return;
        }
    }

    \$data = [
        '$dataEventEntityName' => '$tableName',
        '$dataEventEntityId' => !is_array(\$this->getPrimaryKey()) ? \$this->getPrimaryKey() : null,
        '$dataEventName' => \$this->_eventName,
        '$dataEventEntityForeignKeys' => \$this->getForeignKeys(),
        '$dataEventEntityModifiedColumns' => \$this->_modifiedColumns,
        '$dataEventEntityOriginalValues' => \$this->getOriginalValues(),
        '$dataEventEntityAdditionalValues' => \$this->getAdditionalValues(),
    ];

    $tenantBehavior

    \$this->saveEventBehaviorEntityChange(\$data);

    unset(\$this->_eventName);
    unset(\$this->_modifiedColumns);
    unset(\$this->_isModified);
}
        ";
    }

    /**
     * @return string
     */
    protected function addDeleteEventMethod()
    {
        $tableName = $this->getTableOrFail()->getName();
        $deleteEvent = 'Entity.' . $tableName . '.delete';
        $dataEventEntityName = static::EVENT_CHANGE_ENTITY_NAME;
        $dataEventEntityId = static::EVENT_CHANGE_ENTITY_ID;
        $dataEventEntityForeignKeys = static::EVENT_CHANGE_ENTITY_FOREIGN_KEYS;
        $dataEventName = static::EVENT_CHANGE_NAME;
        $dataEventEntityAdditionalValues = static::EVENT_CHANGE_ENTITY_ADDITIONAL_VALUES;

        // If the TenantBehavior is applied, we need to set the tenant ID in the transfer data
        $tenantBehavior = '';
        if ($this->table->hasBehavior('\Pyz\Zed\TenantBehavior\Persistence\Propel\Behavior\TenantBehavior')) {
            $tenantBehavior = '$data[\'id_tenant\'] = $this->id_tenant;';
        }

        return "
/**
 * @return void
 */
protected function addDeleteEventToMemory()
{
    if (\$this->_isEventDisabled) {
        return;
    }

    \$data = [
        '$dataEventEntityName' => '$tableName',
        '$dataEventEntityId' => !is_array(\$this->getPrimaryKey()) ? \$this->getPrimaryKey() : null,
        '$dataEventName' => '$deleteEvent',
        '$dataEventEntityForeignKeys' => \$this->getForeignKeys(),
        '$dataEventEntityAdditionalValues' => \$this->getAdditionalValues(),
    ];

    $tenantBehavior

    \$this->saveEventBehaviorEntityChange(\$data);
}
        ";
    }

    /**
     * @return string
     */
    protected function addGetForeignKeysMethod()
    {
        return "
/**
 * @return array
 */
protected function getForeignKeys()
{
    \$foreignKeysWithValue = [];
    foreach (\$this->_foreignKeys as \$key => \$value) {
        \$foreignKeysWithValue[\$key] = \$this->getByName(\$value);
    }

    return \$foreignKeysWithValue;
}
        ";
    }

    /**
     * @return string
     */
    protected function addSaveEventBehaviorEntityChangeMethod()
    {
        $maxEventMessageDataSize = $this->getConfig()->getMaxEventMessageDataSize();

        return "
/**
 * @param array \$data
 *
 * @return void
 */
protected function saveEventBehaviorEntityChange(array \$data)
{
    \$encodedData = json_encode(\$data);
    \$dataLength = strlen(\$encodedData);

    if (\$dataLength > $maxEventMessageDataSize * 1024) {
        \$warningMessage = sprintf(
            '%s event message data size (%d KB) exceeds the allowable limit of %d KB. Please reduce the event message size or it might disrupt P&S process.',
            (\$data['event'] ?? ''),
            \$dataLength / 1024,
            $maxEventMessageDataSize,
        );

        \$this->log(\$warningMessage, \\Propel\\Runtime\\Propel::LOG_WARNING);
    }

    \$isInstancePoolingDisabledSuccessfully = \\Propel\\Runtime\\Propel::disableInstancePooling();

    \$spyEventBehaviorEntityChange = new \\Orm\\Zed\\EventBehavior\\Persistence\\SpyEventBehaviorEntityChange();
    \$spyEventBehaviorEntityChange->setData(\$encodedData);
    \$spyEventBehaviorEntityChange->setProcessId(\\Spryker\\Zed\\Kernel\\RequestIdentifier::getRequestId());
    \$spyEventBehaviorEntityChange->save();

    if (\$isInstancePoolingDisabledSuccessfully) {
        \\Propel\\Runtime\\Propel::enableInstancePooling();
    }
}
        ";
    }

    /**
     * @return string
     */
    protected function addIsEventColumnsModifiedMethod()
    {
        $eventParameters = $this->getParameters();
        $tableName = $this->getTableOrFail()->getName();
        $implodedModifiedColumns = '';

        foreach ($eventParameters as $eventParameter) {
            if ($eventParameter['column'] === '*') {
                return "
/**
 * @return bool
 */
protected function isEventColumnsModified()
{
    /* There is a wildcard(*) property for this event */
    return true;
}
            ";
            }
        }

        foreach ($this->getParameters() as $columnAttribute) {
            $implodedAttributes = '';
            foreach ($columnAttribute as $key => $value) {
                $implodedAttributes .= sprintf("
                '$key' => '$value',");
            }

            $implodedModifiedColumns .= sprintf("
            '%s.%s' => [$implodedAttributes
            ],", $tableName, $columnAttribute['column']);
        }

        return "
/**
 * @return bool
 */
protected function isEventColumnsModified()
{
    \$eventColumns = [$implodedModifiedColumns
    ];

    foreach (\$this->_modifiedColumns as \$modifiedColumn) {
        if (isset(\$eventColumns[\$modifiedColumn])) {

            if (!isset(\$eventColumns[\$modifiedColumn]['value'])) {
                return true;
            }

            \$xmlValue = \$eventColumns[\$modifiedColumn]['value'];
            \$xmlValue = \$this->getPhpType(\$xmlValue, \$modifiedColumn);
            \$xmlOperator = '';
            if (isset(\$eventColumns[\$modifiedColumn]['operator'])) {
                \$xmlOperator = \$eventColumns[\$modifiedColumn]['operator'];
            }
            \$before = \$this->_initialValues[\$modifiedColumn];
            \$field = str_replace('$tableName.', '', \$modifiedColumn);
            \$after = \$this->\$field;

            if (\$before === null && \$after !== null) {
                return true;
            }

            if (\$before !== null && \$after === null) {
                return true;
            }

            switch (\$xmlOperator) {
                case '<':
                    \$result = (\$before < \$xmlValue xor \$after < \$xmlValue);
                    break;
                case '>':
                    \$result = (\$before > \$xmlValue xor \$after > \$xmlValue);
                    break;
                case '<=':
                    \$result = (\$before <= \$xmlValue xor \$after <= \$xmlValue);
                    break;
                case '>=':
                    \$result = (\$before >= \$xmlValue xor \$after >= \$xmlValue);
                    break;
                case '<>':
                    \$result = (\$before <> \$xmlValue xor \$after <> \$xmlValue);
                    break;
                case '!=':
                    \$result = (\$before != \$xmlValue xor \$after != \$xmlValue);
                    break;
                case '==':
                    \$result = (\$before == \$xmlValue xor \$after == \$xmlValue);
                    break;
                case '!==':
                    \$result = (\$before !== \$xmlValue xor \$after !== \$xmlValue);
                    break;
                default:
                    \$result = (\$before === \$xmlValue xor \$after === \$xmlValue);
            }

            if (\$result) {
                return true;
            }
        }
    }

    return false;
}
        ";
    }

    /**
     * @return string
     */
    protected function addGetAdditionalValuesMethod()
    {
        $tableName = $this->getTableOrFail()->getName();
        $additionalColumns = $this->getAdditionalColumnNames();
        $implodedAdditionalColumnNames = implode("\n", array_map(function ($columnName) {
            return sprintf("\t'%s',", $columnName);
        }, $additionalColumns));

        return "
/**
 * @return array
 */
protected function getAdditionalValueColumnNames(): array
{
    return [
        $implodedAdditionalColumnNames
    ];
}

/**
 * @return array
 */
protected function getAdditionalValues(): array
{
    \$additionalValues = [];
    foreach (\$this->getAdditionalValueColumnNames() as \$additionalValueColumnName) {
        \$field = str_replace('$tableName.', '', \$additionalValueColumnName);
        \$additionalValues[\$additionalValueColumnName] = \$this->\$field;
    }

    return \$additionalValues;
}
        ";
    }

    /**
     * @return string
     */
    protected function addGetOriginalValuesMethod()
    {
        $tableName = $this->getTableOrFail()->getName();
        $originalValueColumns = $this->getKeepOriginalValueColumnNames();
        $implodedOriginalValueColumnNames = implode("\n", array_map(function ($columnName) {
            return sprintf("\t'%s',", $columnName);
        }, $originalValueColumns));

        return "
/**
 * @return array
 */
protected function getOriginalValueColumnNames(): array
{
    return [
    $implodedOriginalValueColumnNames
    ];
}

/**
 * @return array
 */
protected function getOriginalValues(): array
{
    if (\$this->isNew()) {
        return [];
    }

    \$originalValues = [];
    foreach (\$this->_modifiedColumns as \$modifiedColumn) {
        if (!in_array(\$modifiedColumn, \$this->getOriginalValueColumnNames())) {
            continue;
        }

        \$before = \$this->_initialValues[\$modifiedColumn];
        \$field = str_replace('$tableName.', '', \$modifiedColumn);
        \$after = \$this->\$field;

        if (\$before !== \$after) {
            \$originalValues[\$modifiedColumn] = \$before;
        }
    }

    return \$originalValues;
}
        ";
    }

    /**
     * @return array
     */
    protected function getAdditionalColumnNames(): array
    {
        $additionalColumns = [];
        $tableName = $this->getTableOrFail()->getName();
        $eventColumns = $this->getParameters();
        foreach ($eventColumns as $eventColumn) {
            if ($eventColumn['column'] === '*' && isset($eventColumn['keep-additional']) && $eventColumn['keep-additional'] === 'true') {
                return $this->getTableFullColumnNames();
            }

            if (isset($eventColumn['keep-additional']) && $eventColumn['keep-additional'] === 'true') {
                $additionalColumns[] = $this->formatFullColumnName($tableName, $eventColumn['column']);
            }
        }

        return $additionalColumns;
    }

    /**
     * @return array
     */
    protected function getKeepOriginalValueColumnNames(): array
    {
        $originalValueColumns = [];
        $tableName = $this->getTableOrFail()->getName();
        $eventColumns = $this->getParameters();
        foreach ($eventColumns as $eventColumn) {
            if ($eventColumn['column'] === '*' && isset($eventColumn['keep-original']) && $eventColumn['keep-original'] === 'true') {
                return $this->getTableFullColumnNames();
            }

            if (isset($eventColumn['keep-original']) && $eventColumn['keep-original'] === 'true') {
                $originalValueColumns[] = $this->formatFullColumnName($tableName, $eventColumn['column']);
            }
        }

        return $originalValueColumns;
    }

    /**
     * @return array
     */
    protected function getTableFullColumnNames(): array
    {
        $tableName = $this->getTableOrFail()->getName();

        return array_reduce($this->getTableOrFail()->getColumns(), function ($columns, $columnObj) use ($tableName) {
            $columns[] = $this->formatFullColumnName($tableName, $columnObj->getName());

            return $columns;
        }, []);
    }

    /**
     * @param string $tableName
     * @param string $columnName
     *
     * @return string
     */
    protected function formatFullColumnName(string $tableName, string $columnName): string
    {
        return sprintf('%s.%s', $tableName, $columnName);
    }

    /**
     * @return string
     */
    public function addGetPhpType()
    {
        $tableMapPhpName = sprintf('%s%s', $this->getTableOrFail()->getPhpName(), 'TableMap');

        return "
/**
 * @param string \$xmlValue
 * @param string \$column
 *
 * @return array|bool|\\DateTime|float|int|object
 */
protected function getPhpType(\$xmlValue, \$column)
{
    \$columnType = $tableMapPhpName::getTableMap()->getColumn(\$column)->getType();
    if (in_array(strtoupper(\$columnType), ['INTEGER', 'TINYINT', 'SMALLINT'])) {
        \$xmlValue = (int) \$xmlValue;
    } else if (in_array(strtoupper(\$columnType), ['REAL', 'FLOAT', 'DOUBLE', 'BINARY', 'VARBINARY', 'LONGVARBINARY'])) {
        \$xmlValue = (double) \$xmlValue;
    } else if (strtoupper(\$columnType) === 'ARRAY') {
        \$xmlValue = (array) \$xmlValue;
    } else if (strtoupper(\$columnType) === 'BOOLEAN') {
        \$xmlValue = filter_var(\$xmlValue,  FILTER_VALIDATE_BOOLEAN);
    } else if (strtoupper(\$columnType) === 'OBJECT') {
        \$xmlValue = (object) \$xmlValue;
    } else if (in_array(strtoupper(\$columnType), ['DATE', 'TIME', 'TIMESTAMP', 'BU_DATE', 'BU_TIMESTAMP'])) {
        \$xmlValue = \\DateTime::createFromFormat('Y-m-d H:i:s', \$xmlValue);
    }

    return \$xmlValue;
}
        ";
    }

    /**
     * Returns the table this behavior is applied to
     *
     * @throws \LogicException
     *
     * @return \Propel\Generator\Model\Table
     */
    public function getTableOrFail(): Table
    {
        $table = $this->getTable();

        if ($table === null) {
            throw new LogicException('Table is not defined.');
        }

        return $table;
    }
}
