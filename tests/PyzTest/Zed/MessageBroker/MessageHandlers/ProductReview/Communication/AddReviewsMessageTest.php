<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\MessageBroker\MessageHandlers\ProductReview\Communication;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AddReviewsTransfer;
use Generated\Shared\Transfer\ProductAbstractTransfer;
use Generated\Shared\Transfer\ReviewTransfer;
use PyzTest\Zed\MessageBroker\ProductReviewCommunicationTester;
use Ramsey\Uuid\Uuid;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Zed
 * @group MessageBroker
 * @group MessageHandlers
 * @group ProductReview
 * @group Communication
 * @group AddReviewsMessageTest
 * Add your own group annotations below this line
 */
class AddReviewsMessageTest extends Unit
{
    protected ProductReviewCommunicationTester $tester;

    public function testAddReviewsMessageIsSuccessfullyHandled(): void
    {
        // Arrange
        $channelName = 'product-review-commands';
        $productIdentifier = Uuid::uuid4()->toString();
        $this->tester->haveFullProduct([], [ProductAbstractTransfer::SKU => $productIdentifier]);

        $localeNames = array_keys($this->tester->getLocator()->locale()->facade()->getLocaleCollection());

        $reviewsTransfer = $this->tester->haveReviewTransfer([
            ReviewTransfer::PRODUCT_IDENTIFIER => $productIdentifier,
            ReviewTransfer::LOCALE => reset($localeNames),
        ]);

        $addReviewsTransfer = (new AddReviewsTransfer())->addReview($reviewsTransfer);

        // Act
        $this->tester->setupMessageBroker($addReviewsTransfer::class, $channelName);
        $this->tester->setupMessageBrokerPlugins();
        $messageBrokerFacade = $this->tester->getLocator()->messageBroker()->facade();
        $messageBrokerFacade->sendMessage($addReviewsTransfer);
        $messageBrokerFacade->startWorker($this->tester->buildMessageBrokerWorkerConfigTransfer([$channelName], 1));

        // Assert
        $this->tester->assertReviewExists($reviewsTransfer);
    }
}
