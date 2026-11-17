<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use Generated\Shared\Transfer\DataImporterConfigurationTransfer;
use ReflectionProperty;
use Spryker\Client\Queue\Model\Proxy\QueueProxy;
use Spryker\Shared\PublishAndSynchronizeMetrics\PublishAndSynchronizeMetricsConstants;
use Spryker\Zed\DataImport\Business\Model\DataImporter;
use Spryker\Zed\DataImport\Business\Model\DataImporterCollection;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetStepBroker;
use Spryker\Zed\DataImport\Business\Model\Publisher\DataImporterPublisher;
use Throwable;
use function OpenTelemetry\Instrumentation\hook;

/**
 * Observes data import without modifying any of the classes involved.
 *
 * Each hook is placed on the base class only. The extension also fires a hook registered on a parent
 * class for a subclass method that overrides it, measured on opentelemetry 1.2.1: hooking
 * `DataImporter::import()` therefore covers `DataImporterDataSetWriterAware::import()` and every
 * importer below it, and hooking the subclass as well would record each run twice.
 */
class ImportInstrumentation
{
    /**
     * The publisher's buffer of entity events not yet flushed, keyed by event name.
     *
     * @var string
     */
    protected const string PUBLISHER_EVENT_BUFFER_PROPERTY = 'importedEntityEvents';

    protected static bool $isRegistered = false;

    protected static ?ReflectionProperty $publisherEventBuffer = null;

    /**
     * @return void
     */
    public static function register(): void
    {
        if (static::$isRegistered || !function_exists('OpenTelemetry\Instrumentation\hook')) {
            return;
        }

        static::$isRegistered = true;

        $isRowsEnabled = (bool)getenv(PublishAndSynchronizeMetricsConstants::ENV_IMPORT_ROWS);
        ImportRunCollector::getInstance()->setIsRowsEnabled($isRowsEnabled);

        static::hookImporters();
        static::hookCollections();
        static::hookPublisher();
        static::hookEnqueuedMessages();

        if ($isRowsEnabled) {
            static::hookDataSets();
        }
    }

    /**
     * @return void
     */
    protected static function hookImporters(): void
    {
        hook(
            class: DataImporter::class,
            function: 'import',
            pre: static function (object $dataImporter, array $params): void {
                ImportRunCollector::getInstance()->openImporter(
                    spl_object_id($dataImporter),
                    $dataImporter instanceof DataImporter ? (string)$dataImporter->getImportType() : null,
                    static::readPublisherEventBuffer(),
                );
            },
            post: static function (object $dataImporter, array $params, mixed $response, ?Throwable $throwable): void {
                ImportRunCollector::getInstance()->closeImporter(
                    spl_object_id($dataImporter),
                    $response,
                    $throwable,
                    static::readPublisherEventBuffer(),
                );
            },
        );
    }

    /**
     * @return void
     */
    protected static function hookCollections(): void
    {
        hook(
            class: DataImporterCollection::class,
            function: 'import',
            pre: static function (object $dataImporterCollection, array $params): void {
                ImportRunCollector::getInstance()->openCollection(
                    static::resolveRequestedImportType($dataImporterCollection, $params[0] ?? null),
                );
            },
            post: static function (object $dataImporterCollection, array $params, mixed $response, ?Throwable $throwable): void {
                ImportRunCollector::getInstance()->closeCollection($throwable);
            },
        );
    }

    /**
     * `triggerEvents()` is static. The extension fires hooks on static methods too, measured on
     * opentelemetry 1.2.1 for a direct static call, a `static::` call from inside the class and a
     * call through an instance. The first callback argument is then the class name, not an object,
     * which is why it is typed `mixed` here and never read.
     *
     * @return void
     */
    protected static function hookPublisher(): void
    {
        hook(
            class: DataImporterPublisher::class,
            function: 'triggerEvents',
            pre: static function (mixed $scope, array $params): void {
                ImportRunCollector::getInstance()->openPublishFromBuffer(static::readPublisherEventBuffer());
            },
            post: static function (mixed $scope, array $params, mixed $response, ?Throwable $throwable): void {
                ImportRunCollector::getInstance()->closePublish($throwable);
            },
        );
    }

    /**
     * Counts what is handed to the queue client while a publish span is open. QueueProxy is the
     * single path every producer takes, the event queue producer included.
     *
     * @return void
     */
    protected static function hookEnqueuedMessages(): void
    {
        if (!class_exists(QueueProxy::class)) {
            return;
        }

        ImportRunCollector::getInstance()->setIsMessageCountingAvailable(true);

        hook(
            class: QueueProxy::class,
            function: 'sendMessage',
            pre: static function (object $queueProxy, array $params): void {
                ImportRunCollector::getInstance()->countEnqueuedMessages(1);
            },
        );

        hook(
            class: QueueProxy::class,
            function: 'sendMessages',
            pre: static function (object $queueProxy, array $params): void {
                if (is_array($params[1] ?? null)) {
                    ImportRunCollector::getInstance()->countEnqueuedMessages(count($params[1]));
                }
            },
        );
    }

    /**
     * @return void
     */
    protected static function hookDataSets(): void
    {
        hook(
            class: DataSetStepBroker::class,
            function: 'execute',
            pre: static function (object $dataSetStepBroker, array $params): void {
                ImportRunCollector::getInstance()->openDataSet();
            },
            post: static function (object $dataSetStepBroker, array $params, mixed $response, ?Throwable $throwable): void {
                ImportRunCollector::getInstance()->closeDataSet();
            },
        );
    }

    /**
     * @param object $dataImporterCollection
     * @param mixed $dataImporterConfigurationTransfer
     *
     * @return string|null
     */
    protected static function resolveRequestedImportType(object $dataImporterCollection, mixed $dataImporterConfigurationTransfer): ?string
    {
        // Mirrors DataImporterCollection::getCurrentImportType(): the requested type when one is
        // given, otherwise the collection's own `full`.
        if ($dataImporterConfigurationTransfer instanceof DataImporterConfigurationTransfer && $dataImporterConfigurationTransfer->getImportType()) {
            return $dataImporterConfigurationTransfer->getImportType();
        }

        return $dataImporterCollection instanceof DataImporterCollection ? (string)$dataImporterCollection->getImportType() : null;
    }

    /**
     * The buffer is a protected static with no accessor, and reading it before the flush is the only
     * way to know how many entity events the flush carries without hooking the event facade.
     *
     * @return mixed Null when the property cannot be read, which the collector reports as null.
     */
    protected static function readPublisherEventBuffer(): mixed
    {
        try {
            if (static::$publisherEventBuffer === null) {
                static::$publisherEventBuffer = new ReflectionProperty(
                    DataImporterPublisher::class,
                    static::PUBLISHER_EVENT_BUFFER_PROPERTY,
                );
            }

            return static::$publisherEventBuffer->getValue();
        } catch (Throwable $throwable) {
            error_log(sprintf('Could not read the data import publisher event buffer: %s', $throwable->getMessage()));

            return null;
        }
    }
}
