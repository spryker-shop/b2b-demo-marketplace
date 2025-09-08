<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SynchronizationBehavior\Persistence\Propel\Behavior;

use Laminas\Filter\Word\UnderscoreToCamelCase;
use LogicException;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;
use Propel\Generator\Util\PhpParser;
use Spryker\Zed\Kernel\BundleConfigResolverAwareTrait;
use Spryker\Zed\SynchronizationBehavior\Persistence\Propel\Behavior\Exception\InvalidConfigurationException;
use Spryker\Zed\SynchronizationBehavior\Persistence\Propel\Behavior\Exception\MissingAttributeException;

/**
 * @method \Spryker\Zed\SynchronizationBehavior\SynchronizationBehaviorConfig getConfig()
 */
class SynchronizationBehavior extends Behavior
{
    use BundleConfigResolverAwareTrait;

    /**
     * @var string
     */
    public const ERROR_MISSING_RESOURCE_PARAMETER = '%s misses "resource" synchronization parameter.';

    /**
     * @var string
     */
    public const ERROR_MISSING_MAPPING_RESOURCE_PARAMETER = '%s misses "mapping_resource" synchronization parameter.';

    /**
     * @var string
     */
    public const ERROR_MISSING_MAPPINGS_PARAMETER = '%s misses "mappings" synchronization parameter.';

    /**
     * @var string
     */
    public const ERROR_MUTUALLY_EXCLUSIVE_PARAMETERS = '%s uses mutually exclusive "store" and "queue_pool" synchronization attributes.';

    /**
     * @var string
     */
    public const ERROR_INVALID_MAPPINGS_PARAMETER = '%s define incorrect value of mappings parameter.';

    /**
     * @var string
     */
    protected const SYNCHRONIZATION_ENABLED = 'true';

    /**
     * @var string
     */
    protected const SYNCHRONIZATION_DISABLED = 'false';

    /**
     * @var array<string, mixed>
     */
    protected $parameters = [
        'resource' => null,
        'queue_group' => null,
        'queue_pool' => null,
    ];

    /**
     * @uses \Spryker\Zed\Synchronization\Business\Storage\SynchronizationStorage::DESTINATION_TYPE
     *
     * @var string
     */
    protected const DESTINATION_TYPE_STORAGE = 'storage';

    /**
     * @uses \Spryker\Zed\Synchronization\Business\Search\SynchronizationSearch::DESTINATION_TYPE
     *
     * @var string
     */
    protected const DESTINATION_TYPE_SEARCH = 'search';

    /**
     * @return string
     */
    public function preSave()
    {
        return "
\$this->setGeneratedKey();
\$this->setGeneratedKeyForMappingResource();
\$this->setGeneratedAliasKeys();
        ";
    }

    /**
     * @return string
     */
    public function postSave()
    {
        return "
\$this->syncPublishedMessage();
\$this->syncPublishedMessageForMappingResource();
\$this->syncPublishedMessageForMappings();
        ";
    }

    /**
     * @return string
     */
    public function postDelete()
    {
        return "
\$this->syncUnpublishedMessage();
\$this->syncUnpublishedMessageForMappingResource();
\$this->syncUnpublishedMessageForMappings();
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
     * @return void
     */
    public function addParameter(array $parameter): void
    {
        $parameter = array_change_key_case($parameter, CASE_LOWER);

        // @phpstan-ignore-next-line
        $this->parameters[$parameter['name']] = [];

        if (isset($parameter['value'])) {
            // @phpstan-ignore-next-line
            $this->parameters[$parameter['name']]['value'] = $parameter['value'];
        }

        if (isset($parameter['required'])) {
            // @phpstan-ignore-next-line
            $this->parameters[$parameter['name']]['required'] = $parameter['required'];
        }
    }

    /**
     * @return string
     */
    public function objectAttributes()
    {
        $script = '';
        $script .= $this->addBaseAttribute();

        return $script;
    }

    /**
     * @return string
     */
    public function objectMethods()
    {
        $script = '';
        $script .= $this->addToggleEnqueueMethod();
        $script .= $this->addGetStorageKeyBuilderMethod();
        $script .= $this->addGenerateKeyMethod();
        $script .= $this->addGenerateMappingResourceKeyMethod();
        $script .= $this->addGenerateMappingsKeyMethod();
        $script .= $this->addGenerateAliasKeysMethod();
        $script .= $this->addSendToQueueMethod();
        $script .= $this->addSyncPublishedMessageMethod();
        $script .= $this->addSyncUnpublishedMessageMethod();
        $script .= $this->addSyncPublishedMessageForMappingResourceMethod();
        $script .= $this->addSyncUnpublishedMessageForMappingResourceMethod();
        $script .= $this->addSyncPublishedMessageForMappingsMethod();
        $script .= $this->addSyncUnpublishedMessageForMappingsMethod();
        $script .= $this->addIsSynchronizationEnabledMethod();
        $script .= $this->addGetQueueMethod();
        $script .= $this->addIsDirectSyncEnabledMethod();
        $script .= $this->addSendToBufferMethod();
        $script .= $this->addSendMessageMethod();

        return $script;
    }

    /**
     * @return void
     */
    public function modifyTable(): void
    {
        $table = $this->getTableOrFail();
        $parameters = $this->getParameters();

        if (!$table->hasColumn('data')) {
            $table->addColumn([
                'name' => 'data',
                'type' => $this->getConfig()->getDataColumnType(),
            ]);
        }

        if (isset($parameters['store'])) {
            $required = false;
            if (isset($parameters['store']['required'])) {
                $required = $parameters['store']['required'];
            }

            if (!$table->hasColumn('store')) {
                $table->addColumn([
                    'name' => 'store',
                    'type' => 'VARCHAR',
                    'size' => '128',
                    'required' => $required,
                ]);
            }
        }

        if (isset($parameters['locale'])) {
            $required = false;
            if (isset($parameters['locale']['required'])) {
                $required = $parameters['locale']['required'];
            }

            if (!$table->hasColumn('locale')) {
                $table->addColumn([
                    'name' => 'locale',
                    'type' => 'VARCHAR',
                    'size' => '16',
                    'required' => $required,
                ]);
            }
        }

        if ($this->shouldUseMappingResources()) {
            if (!$table->hasColumn('mapping_resource_key')) {
                $table->addColumn([
                    'name' => 'mapping_resource_key',
                    'type' => 'VARCHAR',
                ]);
                $uniqueIndex = new Unique();
                $uniqueIndex->setName($table->getName() . '-unique-mapping-resource-key');
                $uniqueIndex->addColumn(new Column('mapping_resource_key'));
                $table->addUnique($uniqueIndex);
            }
        }

        if ($this->shouldAddAliasKeysColumn($table)) {
            $table->addColumn([
                'name' => 'alias_keys',
                'type' => 'VARCHAR',
            ]);
            $uniqueIndex = new Unique();
            $uniqueIndex->setName($table->getName() . '-unique-alias-keys');
            $uniqueIndex->addColumn(new Column('alias_keys'));
            $table->addUnique($uniqueIndex);
        }

        if (!$table->hasColumn('key')) {
            $table->addColumn([
                'name' => 'key',
                'type' => 'VARCHAR',
            ]);

            $uniqueIndex = new Unique();
            $uniqueIndex->setName($table->getName() . '-unique-key');
            $uniqueIndex->addColumn(new Column('key'));
            $table->addUnique($uniqueIndex);
        }
    }

    /**
     * @return string
     */
    public function addBaseAttribute()
    {
        return "
/**
 * @var array
 */
private \$_dataTemp;

/**
 * @deprecated Use {@link \Spryker\Zed\SynchronizationBehavior\SynchronizationBehaviorConfig::isSynchronizationEnabled()} instead.
 *
 * @var bool
 */
private \$_isSendingToQueue = true;

/**
 * @var \\Spryker\\Zed\\Kernel\\Locator
 */
private \$_locator;
        ";
    }

    /**
     * @return string
     */
    protected function addToggleEnqueueMethod()
    {
        return "
/**
 * @deprecated Use {@link \Spryker\Zed\SynchronizationBehavior\SynchronizationBehaviorConfig::isSynchronizationEnabled()} instead.
 *
 * @return bool
 */
public function isSendingToQueue()
{
    return \$this->_isSendingToQueue;
}

/**
 * @deprecated Use {@link \Spryker\Zed\SynchronizationBehavior\SynchronizationBehaviorConfig::isSynchronizationEnabled()} instead.
 *
 * @param bool \$_isSendingToQueue
 *
 * @return \$this
 */
public function setIsSendingToQueue(\$_isSendingToQueue)
{
    \$this->_isSendingToQueue = \$_isSendingToQueue;

    return \$this;
}
        ";
    }

    /**
     * @return string
     */
    protected function addGetStorageKeyBuilderMethod()
    {
        return "
/**
 * @param string \$resource
 *
 * @return \\Spryker\\Service\\Synchronization\\Dependency\\Plugin\\SynchronizationKeyGeneratorPluginInterface
 */
protected function getStorageKeyBuilder(\$resource)
{
    if (\$this->_locator === null) {
        \$this->_locator = \\Spryker\\Zed\\Kernel\\Locator::getInstance();
    }

    /** @var \\Spryker\\Service\\Synchronization\\SynchronizationServiceInterface \$synchronizationService */
    \$synchronizationService = \$this->_locator->synchronization()->service();

    return \$synchronizationService->getStorageKeyBuilder(\$resource);
}
        ";
    }

    /**
     * @throws \Spryker\Zed\SynchronizationBehavior\Persistence\Propel\Behavior\Exception\MissingAttributeException
     *
     * @return string
     */
    protected function addGenerateKeyMethod()
    {
        $parameters = $this->getParameters();
        $keySuffix = null;
        $storeSetStatement = $this->getStoreStatement($parameters);
        $localeSetStatement = $this->getLocaleStatement($parameters);
        $tenantSetStatement = $this->getTenantStatement($parameters);
        $referenceSetStatement = '';

        if (!isset($parameters['resource']['value'])) {
            throw new MissingAttributeException(sprintf(static::ERROR_MISSING_RESOURCE_PARAMETER, $this->getTableOrFail()->getPhpName()));
        }

        $resource = $parameters['resource']['value'];

        if (isset($parameters['key_suffix_column'])) {
            $filter = new UnderscoreToCamelCase();
            /** @var string $suffix */
            $suffix = $filter->filter($parameters['key_suffix_column']['value']);
            $keySuffix = sprintf('get%s()', $suffix);
        }

        if ($keySuffix !== null) {
            $referenceSetStatement = "\$syncTransferData->setReference(\$this->$keySuffix);";
        }

        return "
/**
 * @return void
 */
protected function setGeneratedKey()
{
    \$syncTransferData = new \\Generated\\Shared\\Transfer\\SynchronizationDataTransfer();

    if (method_exists(\$this, 'setTenantReferenceFromContainer')) {
        \$this->setTenantReferenceFromContainer();
    }

    $referenceSetStatement
    $storeSetStatement
    $localeSetStatement
    $tenantSetStatement
    \$keyBuilder = \$this->getStorageKeyBuilder('$resource');

    \$key = \$keyBuilder->generateKey(\$syncTransferData);
    \$this->setKey(\$key);
}
        ";
    }

    /**
     * @deprecated Will be removed without replacement.
     *
     * @throws \Spryker\Zed\SynchronizationBehavior\Persistence\Propel\Behavior\Exception\MissingAttributeException
     *
     * @return string
     */
    protected function addGenerateMappingResourceKeyMethod()
    {
        $parameters = $this->getParameters();
        if (!$this->shouldUseMappingResources()) {
            return '/**
 * @return void
 */
protected function setGeneratedKeyForMappingResource()
{
}';
        }
        $keySuffix = null;
        $storeSetStatement = $this->getStoreStatement($parameters);
        $localeSetStatement = $this->getLocaleStatement($parameters);
        $tenantSetStatement = $this->getTenantStatement($parameters);
        $checkSuffixStatement = '';

        if (!isset($parameters['resource']['value'])) {
            throw new MissingAttributeException(sprintf(static::ERROR_MISSING_RESOURCE_PARAMETER, $this->getTableOrFail()->getPhpName()));
        }

        if (!isset($parameters['mapping_resource']['value'])) {
            throw new MissingAttributeException(sprintf(static::ERROR_MISSING_MAPPING_RESOURCE_PARAMETER, $this->getTableOrFail()->getPhpName()));
        }

        $mappingResourceSuffix = "'{$parameters['mapping_resource']['value']}'";
        $referenceSetStatement = "\$syncTransferData->setReference($mappingResourceSuffix);";

        $resource = $parameters['resource']['value'];

        if (isset($parameters['mapping_resource_key_suffix_column'])) {
            $filter = new UnderscoreToCamelCase();
            /** @var string $suffix */
            $suffix = $filter->filter($parameters['mapping_resource_key_suffix_column']['value']);
            $keySuffix = sprintf('get%s()', $suffix);
            $checkSuffixStatement = "if (empty(\$this->$keySuffix)) {
         return;
    }
            ";
        }

        if ($keySuffix !== null) {
            $referenceSetStatement = "\$syncTransferData->setReference($mappingResourceSuffix . ':' .\$this->$keySuffix);";
        }

        return "
/**
 * @return void
 */
protected function setGeneratedKeyForMappingResource()
{
    $checkSuffixStatement
    \$syncTransferData = new \\Generated\\Shared\\Transfer\\SynchronizationDataTransfer();
    $referenceSetStatement
    $storeSetStatement
    $localeSetStatement
    $tenantSetStatement
    \$keyBuilder = \$this->getStorageKeyBuilder('$resource');

    \$key = \$keyBuilder->generateKey(\$syncTransferData);
    \$this->setMappingResourceKey(\$key);
}
        ";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    public function objectFilter(&$script)
    {
        $parser = new PhpParser($script, true);
        $parser->replaceMethod('getData', $this->getNewGetDataMethod());
        $parser->replaceMethod('setData', $this->getNewSetDataMethod());
        $script = $parser->getCode();
    }

    /**
     * @return string
     */
    protected function getNewSetDataMethod()
    {
        $tableName = $this->getTableOrFail()->getPhpName();

        $newCode = "
    /**
     * Set the value of [data] column.
     *
     * @param array \$v new value
     * @return \$this The current object (for fluent API support)
     */
    public function setData(\$v)
    {
        if (is_array(\$v)) {
            \$this->_dataTemp = \$v;
            \$v = json_encode(\$v);
        }

        if (\$v !== null) {
            \$v = (string) \$v;
        }

        if (\$this->data !== \$v) {
            \$this->data = \$v;
            \$this->modifiedColumns[%sTableMap::COL_DATA] = true;
        }

        return \$this;
    }
        ";

        return sprintf($newCode, $tableName);
    }

    /**
     * @return string
     */
    protected function getNewGetDataMethod()
    {
        return "
    /**
     * Get the [data] column value.
     *
     * @return array
     */
    public function getData()
    {
        return json_decode(\$this->data, true);
    }";
    }

    /**
     * @throws \Spryker\Zed\SynchronizationBehavior\Persistence\Propel\Behavior\Exception\InvalidConfigurationException
     *
     * @return string
     */
    protected function addSendToQueueMethod()
    {
        $queuePoolName = $this->getQueuePoolName();
        $hasStore = $this->hasStore();
        $hasLocale = $this->hasLocale();

        if ($hasStore && $queuePoolName) {
            throw new InvalidConfigurationException(
                sprintf(static::ERROR_MUTUALLY_EXCLUSIVE_PARAMETERS, $this->getTableOrFail()->getPhpName()),
            );
        }

        $setLocale = $hasLocale ? '$queueSendTransfer->setLocale($this->locale);' : '';

        $setMessageQueueRouting = '';
        if ($hasStore) {
            $setMessageQueueRouting = '$queueSendTransfer->setStoreName($this->store);';
        }

        if ($queuePoolName) {
            $setMessageQueueRouting = "\$queueSendTransfer->setQueuePoolName('$queuePoolName');";
        }

        return "
/**
 * @param array \$message
 *
 * @return void
 */
protected function sendToQueue(array \$message)
{
    if (\$this->_locator === null) {
        \$this->_locator = \\Spryker\\Zed\\Kernel\\Locator::getInstance();
    }

    \$queueSendTransfer = new \\Generated\\Shared\\Transfer\\QueueSendMessageTransfer();
    \$queueSendTransfer->setBody(json_encode(\$message));
    $setLocale
    $setMessageQueueRouting

    \$queueClient = \$this->_locator->queue()->client();
    \$queueClient->sendMessage(\$this->getQueueName(), \$queueSendTransfer);
}
        ";
    }

    /**
     * @return string
     */
    protected function addSyncPublishedMessageMethod()
    {
        $params = $this->getParams();
        $resource = $this->getParameter('resource')['value'];

        $storeParam = '\'\'';
        if ($this->hasStore()) {
            // @codingStandardsIgnoreLine
            $storeParam = "\$this->store";
        }

        return "
/**
 * @throws PropelException
 *
 * @return void
 */
public function syncPublishedMessage()
{
    if (!\$this->isSynchronizationEnabled()) {
        return;
    }

    // Kept for BC reasons, will be removed in next major.
    if (!\$this->_isSendingToQueue) {
        return;
    }

    if (empty(\$this->getKey())) {
        throw new PropelException(\"Synchronization failed, the column 'key' is null or empty\");
    }

    if (\$this->_dataTemp !== null) {
        \$data = \$this->_dataTemp;
    } else {
        \$data = \$this->getData();
    }

    /* The value for `\$params` has been loaded from schema file */
    \$params = '$params';
    \$decodedParams = [];
    if (!empty(\$params)) {
        \$decodedParams = json_decode(\$params, true);
    }

    \$data['_timestamp'] = microtime(true);
    \$message = [
        'write' => [
            'key' => \$this->getKey(),
            'value' => \$data,
            'resource' => '$resource',
            'store' => $storeParam,
            'params' => \$decodedParams,
        ]
    ];
    \$this->sendMessage(\$message);
}
        ";
    }

    /**
     * @return string
     */
    protected function addSyncUnpublishedMessageMethod()
    {
        $params = $this->getParams();
        $resource = $this->getParameter('resource')['value'];

        $storeParam = '\'\'';
        if ($this->hasStore()) {
            // @codingStandardsIgnoreLine
            $storeParam = "\$this->store";
        }

        return "
/**
 * @return void
 */
public function syncUnpublishedMessage()
{
    if (!\$this->isSynchronizationEnabled()) {
        return;
    }

    // Kept for BC reasons, will be removed in next major.
    if (!\$this->_isSendingToQueue) {
        return;
    }

    /* The value for `\$params` has been loaded from schema file */
    \$params = '$params';
    \$decodedParams = [];
    if (!empty(\$params)) {
        \$decodedParams = json_decode(\$params, true);
    }

    \$data['_timestamp'] = microtime(true);
    \$message = [
        'delete' => [
            'key' => \$this->getKey(),
            'value' => \$data,
            'resource' => '$resource',
            'store' => $storeParam,
            'params' => \$decodedParams,
        ]
    ];

    \$this->sendMessage(\$message);
}
        ";
    }

    /**
     * @deprecated Will be removed without replacement.
     *
     * @return string
     */
    protected function addSyncPublishedMessageForMappingResourceMethod()
    {
        $params = $this->getParams();
        $resource = $this->getParameter('resource')['value'];

        $sendMappingStatement = '';
        if ($this->shouldUseMappingResources()) {
            $sendMappingStatement = "
    if (!empty(\$this->getMappingResourceKey())) {
        /* The value for `\$params` has been loaded from schema file */
        \$params = '$params';
        \$decodedParams = [];
        if (!empty(\$params)) {
            \$decodedParams = json_decode(\$params, true);
        }

        \$message = [
            'write' => [
                'key' => \$this->getMappingResourceKey(),
                'value' => [
                    'key' => \$this->getKey(),
                    '_timestamp' => microtime(true),
                ],
                'resource' => '$resource',
                'params' => \$decodedParams,
            ]
        ];
        \$this->sendMessage(\$message);
    }
            ";
        }

        return "
/**
 * @return void
 */
public function syncPublishedMessageForMappingResource()
{
    if (!\$this->isSynchronizationEnabled()) {
        return;
    }

    $sendMappingStatement;
}
        ";
    }

    /**
     * @deprecated Will be removed without replacement.
     *
     * @return string
     */
    protected function addSyncUnpublishedMessageForMappingResourceMethod()
    {
        $params = $this->getParams();
        $resource = $this->getParameter('resource')['value'];

        $sendMappingStatement = '';
        if ($this->shouldUseMappingResources()) {
            $sendMappingStatement = "
    if (!empty(\$this->getMappingResourceKey())) {
        /* The value for `\$params` has been loaded from schema file */
        \$params = '$params';
        \$decodedParams = [];
        if (!empty(\$params)) {
            \$decodedParams = json_decode(\$params, true);
        }

        \$message = [
            'delete' => [
                'key' => \$this->getMappingResourceKey(),
                'value' => [
                    'key' => \$this->getKey(),
                    '_timestamp' => microtime(true),
                ],
                'resource' => '$resource',
                'params' => \$decodedParams,
            ]
        ];

        \$this->sendMessage(\$message);
    }
            ";
        }

        return "
/**
 * @return void
 */
public function syncUnpublishedMessageForMappingResource()
{
    if (!\$this->isSynchronizationEnabled()) {
        return;
    }

    $sendMappingStatement;
}
        ";
    }

    /**
     * @return string
     */
    protected function addSyncPublishedMessageForMappingsMethod()
    {
        $parameters = $this->getParameters();
        $resource = $this->getParameter('resource')['value'];
        $sendMappingsStatement = '';
        if (isset($parameters['mappings'])) {
            $mappings = $this->getMappings();
            $sendMappingsStatement = "\$mappings = $mappings;
    foreach (\$mappings as \$mapping) {
        \$data = \$this->getData();
        \$source = \$mapping['source'];
        \$destination = \$mapping['destination'];
        if (isset(\$data[\$source]) && isset(\$data[\$destination])) {
            \$message = [
                'write' => [
                    'key' => \$this->generateMappingKey(\$source, \$data[\$source]),
                    'value' => [
                        'id' => \$data[\$destination],
                        '_timestamp' => microtime(true),
                    ],
                    'resource' => '$resource',
                ]
            ];
            \$this->sendMessage(\$message);
        }
    }
            ";
        }

        return "
/**
 * @return void
 */
public function syncPublishedMessageForMappings()
{
    if (!\$this->isSynchronizationEnabled()) {
        return;
    }

    $sendMappingsStatement
}
        ";
    }

    /**
     * @return string
     */
    protected function addSyncUnpublishedMessageForMappingsMethod()
    {
        $parameters = $this->getParameters();
        $resource = $this->getParameter('resource')['value'];
        $sendMappingsStatement = '';
        if (isset($parameters['mappings'])) {
            $mappings = $this->getMappings();
            $sendMappingsStatement = "\$mappings = $mappings;
    foreach (\$mappings as \$mapping) {
        \$data = \$this->getData();
        \$source = \$mapping['source'];
        \$destination = \$mapping['destination'];
        if (isset(\$data[\$source]) && isset(\$data[\$destination])) {
            \$message = [
                'delete' => [
                    'key' => \$this->generateMappingKey(\$source, \$data[\$source]),
                    'value' => [
                        'id' => \$data[\$destination],
                        '_timestamp' => microtime(true),
                    ],
                    'resource' => '$resource',
                ]
            ];
            \$this->sendMessage(\$message);
        }
    }
            ";
        }

        return "
/**
 * @return void
 */
public function syncUnpublishedMessageForMappings()
{
    if (!\$this->isSynchronizationEnabled()) {
        return;
    }

    $sendMappingsStatement
}
        ";
    }

    /**
     * @throws \Spryker\Zed\SynchronizationBehavior\Persistence\Propel\Behavior\Exception\MissingAttributeException
     *
     * @return string
     */
    protected function addGenerateMappingsKeyMethod()
    {
        $parameters = $this->getParameters();
        $storeSetStatement = $this->getStoreStatement($parameters);
        $localeSetStatement = $this->getLocaleStatement($parameters);
        $tenantSetStatement = $this->getTenantStatement($parameters);

        if (!isset($parameters['resource']['value'])) {
            throw new MissingAttributeException(sprintf(static::ERROR_MISSING_RESOURCE_PARAMETER, $this->getTableOrfail()->getPhpName()));
        }

        $resource = $parameters['resource']['value'];

        return "
/**
 * @param string \$source
 * @param string \$sourceIdentifier

 * @return string
 */
protected function generateMappingKey(\$source, \$sourceIdentifier)
{
    \$syncTransferData = new \\Generated\\Shared\\Transfer\\SynchronizationDataTransfer();
    \$syncTransferData->setReference(\$source . ':' . \$sourceIdentifier);
    $storeSetStatement
    $localeSetStatement
    $tenantSetStatement
    \$keyBuilder = \$this->getStorageKeyBuilder('$resource');

    return \$keyBuilder->generateKey(\$syncTransferData);
}
        ";
    }

    /**
     * @return bool
     */
    protected function hasStore()
    {
        return isset($this->getParameters()['store']);
    }

    /**
     * @return bool
     */
    protected function hasLocale(): bool
    {
        return isset($this->getParameters()['locale']);
    }

    /**
     * @return bool
     */
    protected function hasMappings(): bool
    {
        return isset($this->getParameters()['mappings']);
    }

    /**
     * @return string|null
     */
    protected function getQueuePoolName()
    {
        $parameters = $this->getParameters();
        if (!isset($parameters['queue_pool'])) {
            return null;
        }

        if (!isset($parameters['queue_pool']['value'])) {
            return null;
        }

        return $parameters['queue_pool']['value'];
    }

    /**
     * @return string
     */
    protected function getParams()
    {
        $params = '';
        if (isset($this->getParameters()['params'])) {
            $params = $this->getParameters()['params']['value'];
        }

        return $params;
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    protected function getStoreStatement(array $parameters): string
    {
        if (isset($parameters['store'])) {
            return '$syncTransferData->setStore($this->store);';
        }

        return '';
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    protected function getTenantStatement(array $parameters): string
    {
        if ($this->table->hasBehavior('\Go\Zed\TenantBehavior\Persistence\Propel\Behavior\TenantBehavior')) {
            return '$syncTransferData->setTenantReference($this->tenant_reference);';
        }

        return '';
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    protected function getLocaleStatement(array $parameters): string
    {
        if (isset($parameters['locale'])) {
            return '$syncTransferData->setLocale($this->locale);';
        }

        return '';
    }

    /**
     * @throws \Spryker\Zed\SynchronizationBehavior\Persistence\Propel\Behavior\Exception\MissingAttributeException
     *
     * @return string
     */
    protected function getMappings(): string
    {
        if (!$this->hasMappings()) {
            return '';
        }

        $parameters = $this->getParameters();

        if (!isset($parameters['mappings']['value'])) {
            throw new MissingAttributeException(sprintf(static::ERROR_MISSING_MAPPINGS_PARAMETER, $this->getTableOrFail()->getPhpName()));
        }

        return $this->formatMappings($parameters['mappings']['value']);
    }

    /**
     * @param string $mappingsString
     *
     * @return string
     */
    protected function formatMappings(string $mappingsString): string
    {
        $formattedMappings = [];

        $mappings = explode($this->getConfig()->getMappingsDelimiter(), $mappingsString);
        foreach ($mappings as $mapping) {
            $formattedMappings[] = $this->formatSingleMapping($mapping);
        }

        $formattedMappingsString = implode(',', $formattedMappings);
        $formattedMappingsString = <<<EOT
[$formattedMappingsString,
    ]
EOT;

        return $formattedMappingsString;
    }

    /**
     * @param string $mapping
     *
     * @throws \Spryker\Zed\SynchronizationBehavior\Persistence\Propel\Behavior\Exception\InvalidConfigurationException
     *
     * @return string
     */
    protected function formatSingleMapping(string $mapping): string
    {
        $mappingParts = explode(':', $mapping);

        if (count($mappingParts) !== 2) {
            throw new InvalidConfigurationException(
                sprintf(static::ERROR_INVALID_MAPPINGS_PARAMETER, $this->getTableOrFail()->getPhpName()),
            );
        }

        return "
        [
            'source' => '{$mappingParts[0]}',
            'destination' => '{$mappingParts[1]}',
        ]";
    }

    /**
     * @return string
     */
    protected function addGenerateAliasKeysMethod(): string
    {
        if (!$this->shouldSetAliasKeys()) {
            return '/**
 * @return void
 */
protected function setGeneratedAliasKeys()
{
}';
        }
        $mappings = $this->getMappings();

        return "
/**
 * @return void
 */
protected function setGeneratedAliasKeys()
{
    \$mappings = $mappings;
    \$data = \$this->getData();
    \$aliasKeys = json_decode(\$this->getAliasKeys(), true) ?? [];
    if (count(\$mappings) > 1) {
        throw new \\Spryker\\Zed\\SynchronizationBehavior\\Persistence\\Propel\\Behavior\\Exception\\InvalidConfigurationException(
            sprintf('Multiple mapping is not supported! ' . \$this->getTableOrFail()->getPhpName()),
        );
    }
    foreach (\$mappings as \$mapping) {
        \$source = \$mapping['source'];
        \$destination = \$mapping['destination'];
        if (isset(\$data[\$source]) && isset(\$data[\$destination])) {
            \$key = \$this->generateMappingKey(\$source, \$data[\$source]);
            \$this->setAliasKeys(\$key);
            return;
        }
    }
}
        ";
    }

    /**
     * @return string
     */
    protected function addIsSynchronizationEnabledMethod(): string
    {
        $isSynchronizationEnabled = $this->isSynchronizationEnabled()
            ? static::SYNCHRONIZATION_ENABLED
            : static::SYNCHRONIZATION_DISABLED;

        return "
/**
 * @return bool
 */
public function isSynchronizationEnabled(): bool
{
    return $isSynchronizationEnabled;
}
        ";
    }

    /**
     * @return bool
     */
    protected function isSynchronizationEnabled(): bool
    {
        $parameters = $this->getParameters();

        if (isset($parameters['synchronization_enabled']) && isset($parameters['synchronization_enabled']['value'])) {
            return $parameters['synchronization_enabled']['value'] === static::SYNCHRONIZATION_ENABLED;
        }

        return $this->getConfig()->isSynchronizationEnabled();
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return bool
     */
    protected function shouldAddAliasKeysColumn(Table $table): bool
    {
        return $this->getConfig()->isAliasKeysEnabled() && !$table->hasColumn('alias_keys');
    }

    /**
     * @return bool
     */
    protected function shouldSetAliasKeys(): bool
    {
        return $this->getConfig()->isAliasKeysEnabled() && $this->hasMappings();
    }

    /**
     * @deprecated For BC reasons only. Will be removed along with the concept of mapping resources.
     *
     * @return bool
     */
    protected function shouldUseMappingResources(): bool
    {
        $parameters = $this->getParameters();

        return isset($parameters['mapping_resource']) && !isset($parameters['mappings']);
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

    /**
     * @return bool
     */
    protected function isDirectSyncPerTableDisabled(): bool
    {
        return isset($this->getParameters()['direct_sync_disabled']);
    }

    /**
     * @return string
     */
    protected function addIsDirectSyncEnabledMethod(): string
    {
        $isDirectSynchronizationEnabled = $this->getConfig()->isDirectSynchronizationEnabled();
        $isDirectSyncPerTableDisabled = $this->isDirectSyncPerTableDisabled();

        $isDirectSyncEnabled = ($isDirectSynchronizationEnabled && !$isDirectSyncPerTableDisabled)
            ? static::SYNCHRONIZATION_ENABLED
            : static::SYNCHRONIZATION_DISABLED;

        return "
/**
 * @return bool
 */
protected function isDirectSyncEnabled(): bool
{
    return $isDirectSyncEnabled;
}
        ";
    }

    /**
     * @return string
     */
    protected function addGetQueueMethod(): string
    {
        $queueName = $this->getParameter('queue_group')['value'];
        if ($queueName === null) {
            $queueName = $this->getParameter('resource')['value'];
        }

        return "
/**
 * @return string
 */
protected function getQueueName(): string
{
    return '$queueName';
}
        ";
    }

    /**
     * @return string
     */
    protected function addSendMessageMethod(): string
    {
        return "
/**
 * @param array \$message
 *
 * @return void
 */
protected function sendMessage(array \$message): void
{
    if (\$this->_locator === null) {
        \$this->_locator = \\Spryker\\Zed\\Kernel\\Locator::getInstance();
    }

    \$synchronizationFacade = \$this->_locator->synchronization()->facade();
    if (\$this->isDirectSyncEnabled() && method_exists(\$synchronizationFacade, 'addSynchronizationMessageToBuffer')) {
        \$this->sendToBuffer(\$message);

        return;
    }

    \$this->sendToQueue(\$message);
}
        ";
    }

    /**
     * @throws \Spryker\Zed\SynchronizationBehavior\Persistence\Propel\Behavior\Exception\InvalidConfigurationException
     *
     * @return string
     */
    protected function addSendToBufferMethod(): string
    {
        $resource = $this->getParameter('resource')['value'];
        $queuePoolName = $this->getQueuePoolName();
        $hasStore = $this->hasStore();
        $hasLocale = $this->hasLocale();

        if ($hasStore && $queuePoolName) {
            throw new InvalidConfigurationException(
                sprintf(static::ERROR_MUTUALLY_EXCLUSIVE_PARAMETERS, $this->getTableOrFail()->getPhpName()),
            );
        }

        $setLocale = $hasLocale ? '$synchronizationMessageTransfer->setLocale($this->locale);' : '';

        $setMessageQueueRouting = '';
        if ($hasStore) {
            $setMessageQueueRouting = '$queueSendTransfer->setStoreName($this->store);';
        }

        if ($queuePoolName) {
            $setMessageQueueRouting = "\$queueSendTransfer->setQueuePoolName('$queuePoolName');";
        }

        $tableName = $this->getTableOrFail()->getName();
        $syncDestinationType = preg_match('/_storage$/', $tableName)
            ? static::DESTINATION_TYPE_STORAGE
            : (preg_match('/_search$/', $tableName) ? static::DESTINATION_TYPE_SEARCH : '');

        return "
/**
 * @param array \$message
 *
 * @return void
 */
protected function sendToBuffer(array \$message): void
{
    \$queueSendTransfer = new \\Generated\\Shared\\Transfer\\QueueSendMessageTransfer();
    \$queueSendTransfer->setBody(json_encode(\$message));
    $setMessageQueueRouting

    \$operationKey = array_key_first(\$message);
    \$synchronizationMessageTransfer = new \\Generated\\Shared\\Transfer\\SynchronizationMessageTransfer();
    \$synchronizationMessageTransfer->setData(\$message[\$operationKey] ?? []);
    \$synchronizationMessageTransfer->setFallbackQueueMessage(\$queueSendTransfer);
    \$synchronizationMessageTransfer->setFallbackQueueName(\$this->getQueueName());
    \$synchronizationMessageTransfer->setSyncDestinationType('$syncDestinationType');
    \$synchronizationMessageTransfer->setOperationType(\$operationKey);
    \$synchronizationMessageTransfer->setResource('$resource');
    $setLocale

    if (\$this->_locator === null) {
        \$this->_locator = \\Spryker\\Zed\\Kernel\\Locator::getInstance();
    }

    \$synchronizationFacade = \$this->_locator->synchronization()->facade();
    \$synchronizationFacade->addSynchronizationMessageToBuffer(\$synchronizationMessageTransfer);
}
        ";
    }
}
