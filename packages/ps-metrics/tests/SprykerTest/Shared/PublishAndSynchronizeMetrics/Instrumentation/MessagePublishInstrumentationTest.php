<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\QueueSendMessageTransfer;
use Spryker\Shared\PublishAndSynchronizeMetrics\PublishAndSynchronizeMetricsConstants;
use SprykerTest\Shared\PublishAndSynchronizeMetrics\MessagePublishInstrumentationProxy;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Shared
 * @group PublishAndSynchronizeMetrics
 * @group Instrumentation
 * @group MessagePublishInstrumentationTest
 * Add your own group annotations below this line
 */
class MessagePublishInstrumentationTest extends Unit
{
    /**
     * @return void
     */
    public function testStampsAMessageWithAPublishTimestamp(): void
    {
        // Arrange
        $queueSendMessageTransfer = (new QueueSendMessageTransfer())->setBody('{}');

        // Act
        $queueSendMessageTransfer = MessagePublishInstrumentationProxy::stampMessage($queueSendMessageTransfer);

        // Assert
        $this->assertArrayHasKey(
            PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT,
            $queueSendMessageTransfer->getHeaders(),
        );
    }

    /**
     * @return void
     */
    public function testStampsWithMicrosecondResolution(): void
    {
        // Arrange
        // Whole seconds cannot describe batches that finish in tens of milliseconds, which is the
        // only reason this header exists rather than reusing the event payload timestamp.
        $queueSendMessageTransfer = (new QueueSendMessageTransfer())->setBody('{}');

        // Act
        $queueSendMessageTransfer = MessagePublishInstrumentationProxy::stampMessage($queueSendMessageTransfer);

        // Assert
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d{6}$/',
            $queueSendMessageTransfer->getHeaders()[PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT],
        );
    }

    /**
     * @return void
     */
    public function testKeepsTheOriginalTimestampWhenAMessageIsRepublished(): void
    {
        // Arrange
        // A retried message must report its age end to end, not restart the clock on each attempt.
        $queueSendMessageTransfer = (new QueueSendMessageTransfer())
            ->setBody('{}')
            ->setHeaders([PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT => '1700000000.123456']);

        // Act
        $queueSendMessageTransfer = MessagePublishInstrumentationProxy::stampMessage($queueSendMessageTransfer);

        // Assert
        $this->assertSame(
            '1700000000.123456',
            $queueSendMessageTransfer->getHeaders()[PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT],
        );
    }

    /**
     * @return void
     */
    public function testPreservesHeadersTheMessageAlreadyCarries(): void
    {
        // Arrange
        // Instrumentation that drops a routing header would change delivery, not just observe it.
        $queueSendMessageTransfer = (new QueueSendMessageTransfer())
            ->setBody('{}')
            ->setHeaders(['x-existing' => 'value']);

        // Act
        $queueSendMessageTransfer = MessagePublishInstrumentationProxy::stampMessage($queueSendMessageTransfer);

        // Assert
        $headers = $queueSendMessageTransfer->getHeaders();
        $this->assertSame('value', $headers['x-existing']);
        $this->assertArrayHasKey(PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT, $headers);
    }

    /**
     * @return void
     */
    public function testStampsAMessageThatCarriesNoHeadersAtAll(): void
    {
        // Arrange
        $queueSendMessageTransfer = (new QueueSendMessageTransfer())->setBody('{}');

        // Act
        $queueSendMessageTransfer = MessagePublishInstrumentationProxy::stampMessage($queueSendMessageTransfer);

        // Assert
        $this->assertCount(1, $queueSendMessageTransfer->getHeaders());
    }

    /**
     * @return void
     */
    public function testStampIsMonotonicAcrossSuccessivePublishes(): void
    {
        // Arrange
        $first = MessagePublishInstrumentationProxy::stampMessage((new QueueSendMessageTransfer())->setBody('{}'));
        $second = MessagePublishInstrumentationProxy::stampMessage((new QueueSendMessageTransfer())->setBody('{}'));

        // Act
        $firstStamp = (float)$first->getHeaders()[PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT];
        $secondStamp = (float)$second->getHeaders()[PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT];

        // Assert
        $this->assertGreaterThanOrEqual($firstStamp, $secondStamp);
    }

    /**
     * @return void
     */
    public function testNeverWritesTheAttemptStampOnFirstSend(): void
    {
        // Arrange
        $queueSendMessageTransfer = (new QueueSendMessageTransfer())->setBody('{"eventName":"Entity.spy_product.update"}');

        // Act
        $queueSendMessageTransfer = MessagePublishInstrumentationProxy::stampMessage($queueSendMessageTransfer);

        // Assert
        $this->assertArrayNotHasKey(
            PublishAndSynchronizeMetricsConstants::HEADER_ATTEMPT_ENQUEUED_AT,
            $queueSendMessageTransfer->getHeaders(),
        );
        $this->assertStringNotContainsString(
            PublishAndSynchronizeMetricsConstants::HEADER_ATTEMPT_ENQUEUED_AT,
            (string)$queueSendMessageTransfer->getBody(),
        );
    }

    /**
     * The body key exists only for the retry republish, which drops the header; on first send the
     * header is the carrier and the body must reach the broker exactly as the producer wrote it.
     *
     * @return void
     */
    public function testFirstSendWritesNoBodyKey(): void
    {
        // Arrange
        $body = '{"eventName":"Entity.spy_product.update"}';
        $queueSendMessageTransfer = (new QueueSendMessageTransfer())->setBody($body);

        // Act
        $queueSendMessageTransfer = MessagePublishInstrumentationProxy::stampMessage($queueSendMessageTransfer);

        // Assert
        $this->assertSame($body, $queueSendMessageTransfer->getBody());
    }
}
