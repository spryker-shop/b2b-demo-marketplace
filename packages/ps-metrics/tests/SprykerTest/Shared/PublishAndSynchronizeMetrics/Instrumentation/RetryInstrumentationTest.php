<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generated\Shared\Transfer\QueueSendMessageTransfer;
use Spryker\Shared\PublishAndSynchronizeMetrics\PublishAndSynchronizeMetricsConstants;
use SprykerTest\Shared\PublishAndSynchronizeMetrics\RetryInstrumentationProxy;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Shared
 * @group PublishAndSynchronizeMetrics
 * @group Instrumentation
 * @group RetryInstrumentationTest
 * Add your own group annotations below this line
 */
class RetryInstrumentationTest extends Unit
{
    /**
     * @return void
     */
    public function testWritesTheAttemptStampIntoTheBodyWhenAMessageIsSentBackForRetry(): void
    {
        // Arrange
        $before = microtime(true);
        $queueReceiveMessageTransfer = $this->createMessage('{"eventName":"Entity.spy_product.update","retry":0}');

        // Act
        RetryInstrumentationProxy::stampAttemptOf($queueReceiveMessageTransfer);

        // Assert
        $body = $this->decodeBody($queueReceiveMessageTransfer);
        $this->assertArrayHasKey(PublishAndSynchronizeMetricsConstants::HEADER_ATTEMPT_ENQUEUED_AT, $body);
        $this->assertGreaterThanOrEqual($before, (float)$body[PublishAndSynchronizeMetricsConstants::HEADER_ATTEMPT_ENQUEUED_AT]);
    }

    /**
     * @return void
     */
    public function testWritesTheAttemptStampWithMicrosecondResolution(): void
    {
        // Arrange
        $queueReceiveMessageTransfer = $this->createMessage('{}');

        // Act
        RetryInstrumentationProxy::stampAttemptOf($queueReceiveMessageTransfer);

        // Assert
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d{6}$/',
            $this->decodeBody($queueReceiveMessageTransfer)[PublishAndSynchronizeMetricsConstants::HEADER_ATTEMPT_ENQUEUED_AT],
        );
    }

    /**
     * Each retry starts a new attempt, so the stamp of an earlier attempt must not survive it.
     *
     * @return void
     */
    public function testReplacesTheStampOfAnEarlierAttempt(): void
    {
        // Arrange
        $queueReceiveMessageTransfer = $this->createMessage(
            sprintf('{"%s":"1700000000.123456"}', PublishAndSynchronizeMetricsConstants::HEADER_ATTEMPT_ENQUEUED_AT),
        );

        // Act
        RetryInstrumentationProxy::stampAttemptOf($queueReceiveMessageTransfer);

        // Assert
        $this->assertNotSame(
            '1700000000.123456',
            $this->decodeBody($queueReceiveMessageTransfer)[PublishAndSynchronizeMetricsConstants::HEADER_ATTEMPT_ENQUEUED_AT],
        );
    }

    /**
     * Instrumentation that dropped the retry counter would turn a bounded retry into an endless one.
     *
     * @return void
     */
    public function testKeepsEveryKeyTheBodyAlreadyCarries(): void
    {
        // Arrange
        $queueReceiveMessageTransfer = $this->createMessage('{"eventName":"Entity.spy_product.update","retry":1,"transferData":{"id":7}}');

        // Act
        RetryInstrumentationProxy::stampAttemptOf($queueReceiveMessageTransfer);

        // Assert
        $body = $this->decodeBody($queueReceiveMessageTransfer);
        $this->assertSame('Entity.spy_product.update', $body['eventName']);
        $this->assertSame(1, $body['retry']);
        $this->assertSame(['id' => 7], $body['transferData']);
    }

    /**
     * @return void
     */
    public function testLeavesABodyThatIsNotAJsonObjectUntouched(): void
    {
        // Arrange
        $queueReceiveMessageTransfer = $this->createMessage('not json');

        // Act
        RetryInstrumentationProxy::stampAttemptOf($queueReceiveMessageTransfer);

        // Assert
        $this->assertSame('not json', $queueReceiveMessageTransfer->getQueueMessage()->getBody());
    }

    /**
     * @return void
     */
    public function testCarriesTheFirstSendStampFromTheHeaderIntoTheBodyOnTheFirstRetry(): void
    {
        // Arrange
        $queueReceiveMessageTransfer = $this->createMessage('{"retry":0}');
        $queueReceiveMessageTransfer->getQueueMessage()->setHeaders([
            PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT => '1700000000.123456',
        ]);

        // Act
        RetryInstrumentationProxy::stampAttemptOf($queueReceiveMessageTransfer);

        // Assert
        $this->assertSame(
            '1700000000.123456',
            $this->decodeBody($queueReceiveMessageTransfer)[PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT],
        );
    }

    /**
     * The republish drops every header, so the second retry arrives with the body key only; the
     * first-send time must come through unchanged while the attempt stamp moves on.
     *
     * @return void
     */
    public function testTheFirstSendStampSurvivesTwoRetryRepublishesUnchanged(): void
    {
        // Arrange
        $firstAttempt = $this->createMessage('{"retry":0}');
        $firstAttempt->getQueueMessage()->setHeaders([
            PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT => '1700000000.123456',
        ]);
        RetryInstrumentationProxy::stampAttemptOf($firstAttempt);
        $secondAttempt = $this->createMessage((string)$firstAttempt->getQueueMessage()->getBody());
        $firstAttemptStamp = $this->decodeBody($secondAttempt)[PublishAndSynchronizeMetricsConstants::HEADER_ATTEMPT_ENQUEUED_AT];
        usleep(10);

        // Act
        RetryInstrumentationProxy::stampAttemptOf($secondAttempt);
        $thirdAttempt = $this->createMessage((string)$secondAttempt->getQueueMessage()->getBody());

        // Assert
        $body = $this->decodeBody($thirdAttempt);
        $this->assertSame('1700000000.123456', $body[PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT]);
        $this->assertNotSame($firstAttemptStamp, $body[PublishAndSynchronizeMetricsConstants::HEADER_ATTEMPT_ENQUEUED_AT]);
    }

    /**
     * @return void
     */
    public function testKeepsAFirstSendStampTheBodyAlreadyCarries(): void
    {
        // Arrange
        $queueReceiveMessageTransfer = $this->createMessage(
            sprintf('{"%s":"1700000000.123456"}', PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT),
        );
        $queueReceiveMessageTransfer->getQueueMessage()->setHeaders([
            PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT => '1800000000.000000',
        ]);

        // Act
        RetryInstrumentationProxy::stampAttemptOf($queueReceiveMessageTransfer);

        // Assert
        $this->assertSame(
            '1700000000.123456',
            $this->decodeBody($queueReceiveMessageTransfer)[PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT],
        );
    }

    /**
     * A message published while instrumentation was off has no first-send time; inventing one at
     * retry time would report the attempt age as the end-to-end age.
     *
     * @return void
     */
    public function testWritesNoFirstSendBodyKeyWhenTheMessageNeverCarriedAStamp(): void
    {
        // Arrange
        $queueReceiveMessageTransfer = $this->createMessage('{"retry":0}');

        // Act
        RetryInstrumentationProxy::stampAttemptOf($queueReceiveMessageTransfer);

        // Assert
        $this->assertArrayNotHasKey(
            PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT,
            $this->decodeBody($queueReceiveMessageTransfer),
        );
    }

    /**
     * @param string $body
     *
     * @return \Generated\Shared\Transfer\QueueReceiveMessageTransfer
     */
    protected function createMessage(string $body): QueueReceiveMessageTransfer
    {
        return (new QueueReceiveMessageTransfer())
            ->setQueueMessage((new QueueSendMessageTransfer())->setBody($body));
    }

    /**
     * @param \Generated\Shared\Transfer\QueueReceiveMessageTransfer $queueReceiveMessageTransfer
     *
     * @return array<string, mixed>
     */
    protected function decodeBody(QueueReceiveMessageTransfer $queueReceiveMessageTransfer): array
    {
        return json_decode((string)$queueReceiveMessageTransfer->getQueueMessage()->getBody(), true);
    }
}
