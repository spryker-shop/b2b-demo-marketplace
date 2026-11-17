<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\PublishAndSynchronizeMetrics\Metrics;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generated\Shared\Transfer\QueueSendMessageTransfer;
use Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\MessageAgeCalculator;
use Spryker\Shared\PublishAndSynchronizeMetrics\PublishAndSynchronizeMetricsConstants;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Shared
 * @group PublishAndSynchronizeMetrics
 * @group Metrics
 * @group MessageAgeCalculatorTest
 * Add your own group annotations below this line
 */
class MessageAgeCalculatorTest extends Unit
{
    /**
     * @var float
     */
    protected const float TOLERANCE_MS = 250.0;

    /**
     * @return void
     */
    public function testReturnsEmptyArrayWhenThereAreNoMessages(): void
    {
        // Act
        $ages = (new MessageAgeCalculator())->calculate([]);

        // Assert
        $this->assertSame([], $ages);
    }

    /**
     * @return void
     */
    public function testReturnsEmptyArrayWhenNoMessageCarriesAPublishTimestamp(): void
    {
        // Arrange
        $messages = [
            $this->createMessage(null),
            $this->createMessage(null),
        ];

        // Act
        $ages = (new MessageAgeCalculator())->calculate($messages);

        // Assert
        $this->assertSame(
            [],
            $ages,
            'Without a publish timestamp there is no age to report, and reporting zero would be a lie.',
        );
    }

    /**
     * @return void
     */
    public function testCountsOnlyTheMessagesThatCarryATimestamp(): void
    {
        // Arrange
        $now = microtime(true);
        $messages = [
            $this->createMessage($now - 1.0),
            $this->createMessage(null),
            $this->createMessage($now - 2.0),
        ];

        // Act
        $ages = (new MessageAgeCalculator())->calculate($messages);

        // Assert
        $this->assertSame(2, $ages['n'], 'The unstamped message must not be counted.');
    }

    /**
     * @return void
     */
    public function testCalculatesTheAgeDistributionInMilliseconds(): void
    {
        // Arrange
        $now = microtime(true);
        $messages = [
            $this->createMessage($now - 0.100),
            $this->createMessage($now - 0.200),
            $this->createMessage($now - 0.300),
            $this->createMessage($now - 0.400),
        ];

        // Act
        $ages = (new MessageAgeCalculator())->calculate($messages);

        // Assert
        $this->assertSame(4, $ages['n']);
        $this->assertEqualsWithDelta(100.0, $ages['min'], static::TOLERANCE_MS);
        $this->assertEqualsWithDelta(400.0, $ages['max'], static::TOLERANCE_MS);
        $this->assertEqualsWithDelta(250.0, $ages['avg'], static::TOLERANCE_MS);
        $this->assertGreaterThanOrEqual($ages['min'], $ages['p50']);
        $this->assertLessThanOrEqual($ages['max'], $ages['p95']);
    }

    /**
     * @return void
     */
    public function testIgnoresANonNumericTimestamp(): void
    {
        // Arrange
        $message = new QueueReceiveMessageTransfer();
        $message->setQueueMessage(
            (new QueueSendMessageTransfer())->setHeaders([
                PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT => 'not-a-timestamp',
            ]),
        );

        // Act
        $ages = (new MessageAgeCalculator())->calculate([$message]);

        // Assert
        $this->assertSame([], $ages, 'A malformed header must be skipped rather than parsed as zero.');
    }

    /**
     * @return void
     */
    public function testReportsASingleMessageWithoutDividingByZero(): void
    {
        // Arrange
        $messages = [$this->createMessage(microtime(true) - 0.050)];

        // Act
        $ages = (new MessageAgeCalculator())->calculate($messages);

        // Assert
        $this->assertSame(1, $ages['n']);
        $this->assertSame($ages['min'], $ages['max']);
        $this->assertSame($ages['min'], $ages['p95']);
    }

    /**
     * @return void
     */
    public function testAttemptHistogramIsEmptyWhenNoMessageCarriesAnAttemptStamp(): void
    {
        // Arrange
        $messages = [
            $this->createMessage(microtime(true) - 0.050),
            $this->createMessage(null),
        ];

        // Act
        $histogram = (new MessageAgeCalculator())->calculateAttemptHistogram($messages);

        // Assert
        $this->assertSame([], $histogram, 'A first attempt has no attempt age, and zero would be a measurement.');
    }

    /**
     * @return void
     */
    public function testAttemptHistogramCountsOnlyMessagesThatCarryAnAttemptStamp(): void
    {
        // Arrange
        $messages = [
            $this->createRetriedMessage(microtime(true) - 0.100),
            $this->createRetriedMessage(microtime(true) - 0.200),
            $this->createMessage(null),
        ];

        // Act
        $histogram = (new MessageAgeCalculator())->calculateAttemptHistogram($messages);

        // Assert
        $this->assertSame(2, $histogram['n']);
    }

    /**
     * The publish-time header is in the message properties, the attempt stamp in the body; the
     * attempt age must come from the body even when the header says the message is much older.
     *
     * @return void
     */
    public function testAttemptHistogramMeasuresTheAgeOfTheCurrentAttemptNotOfTheFirstPublish(): void
    {
        // Arrange
        $message = $this->createRetriedMessage(microtime(true) - 0.050);
        $message->getQueueMessage()->setHeaders([
            PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT => sprintf('%.6F', microtime(true) - 60.0),
        ]);

        // Act
        $histogram = (new MessageAgeCalculator())->calculateAttemptHistogram([$message]);

        // Assert
        $this->assertSame(1, $histogram['n']);
        $this->assertLessThan(50.0 + static::TOLERANCE_MS, $histogram['sum']);
    }

    /**
     * @return void
     */
    public function testAttemptHistogramIgnoresANonNumericAttemptStamp(): void
    {
        // Arrange
        $message = $this->createMessage(null);
        $message->getQueueMessage()->setBody(
            json_encode([PublishAndSynchronizeMetricsConstants::HEADER_ATTEMPT_ENQUEUED_AT => 'not-a-time']),
        );

        // Act
        $histogram = (new MessageAgeCalculator())->calculateAttemptHistogram([$message]);

        // Assert
        $this->assertSame([], $histogram);
    }

    /**
     * @return void
     */
    public function testReadsTheFirstSendStampFromTheBodyWhenTheRetryRepublishDroppedTheHeader(): void
    {
        // Arrange
        $message = $this->createMessage(null);
        $message->getQueueMessage()->setBody((string)json_encode([
            PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT => sprintf('%.6F', microtime(true) - 2.0),
        ]));

        // Act
        $ages = (new MessageAgeCalculator())->calculate([$message]);

        // Assert
        $this->assertSame(1, $ages['n']);
        $this->assertGreaterThanOrEqual(2000.0, $ages['min']);
    }

    /**
     * @return void
     */
    public function testABodyStampNeverOverridesAPresentHeader(): void
    {
        // Arrange
        $message = $this->createMessage(microtime(true) - 0.050);
        $message->getQueueMessage()->setBody((string)json_encode([
            PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT => sprintf('%.6F', microtime(true) - 60.0),
        ]));

        // Act
        $ages = (new MessageAgeCalculator())->calculate([$message]);

        // Assert
        $this->assertLessThan(50.0 + static::TOLERANCE_MS, $ages['max']);
    }

    /**
     * @return void
     */
    public function testDoesNotMistakeTheAttemptStampForTheFirstSendStamp(): void
    {
        // Arrange
        $messages = [$this->createRetriedMessage(microtime(true) - 0.050)];

        // Act
        $ages = (new MessageAgeCalculator())->calculate($messages);

        // Assert
        $this->assertSame([], $ages);
    }

    /**
     * @param float $attemptEnqueuedAt
     *
     * @return \Generated\Shared\Transfer\QueueReceiveMessageTransfer
     */
    protected function createRetriedMessage(float $attemptEnqueuedAt): QueueReceiveMessageTransfer
    {
        $queueReceiveMessageTransfer = $this->createMessage(null);
        $queueReceiveMessageTransfer->getQueueMessage()->setBody((string)json_encode([
            'eventName' => 'Entity.spy_product.update',
            'retry' => 1,
            PublishAndSynchronizeMetricsConstants::HEADER_ATTEMPT_ENQUEUED_AT => sprintf('%.6F', $attemptEnqueuedAt),
        ]));

        return $queueReceiveMessageTransfer;
    }

    /**
     * @param float|null $enqueuedAt
     *
     * @return \Generated\Shared\Transfer\QueueReceiveMessageTransfer
     */
    protected function createMessage(?float $enqueuedAt): QueueReceiveMessageTransfer
    {
        $queueSendMessageTransfer = new QueueSendMessageTransfer();

        if ($enqueuedAt !== null) {
            $queueSendMessageTransfer->setHeaders([
                PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT => sprintf('%.6F', $enqueuedAt),
            ]);
        }

        return (new QueueReceiveMessageTransfer())->setQueueMessage($queueSendMessageTransfer);
    }
}
