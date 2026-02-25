<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\Customer;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RestCustomersAttributesTransfer;
use SprykerTest\Glue\Testify\Tester\ApiEndToEndTester;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(\PyzTest\Glue\Customer\PHPMD)
 */
class CustomerApiTester extends ApiEndToEndTester
{
    use _generated\CustomerApiTesterActions;

    /**
     * @param \Generated\Shared\Transfer\CustomerTransfer $customerTransfer
     * @param array<string> $restCustomersAttributesTransferData
     *
     * @return void
     */
    public function assertCustomersAttributes(
        CustomerTransfer $customerTransfer,
        array $restCustomersAttributesTransferData,
    ): void {
        $restCustomersAttributesTransfer = (new RestCustomersAttributesTransfer())
            ->fromArray($restCustomersAttributesTransferData, true);

        $this->assertSame(
            $customerTransfer->getEmail(),
            $restCustomersAttributesTransfer->getEmail(),
        );
        $this->assertSame(
            $customerTransfer->getFirstName(),
            $restCustomersAttributesTransfer->getFirstName(),
        );
        $this->assertSame(
            $customerTransfer->getLastName(),
            $restCustomersAttributesTransfer->getLastName(),
        );
        // Compare only the date and time up to seconds, allowing for minor timing differences
        // createdAt should be close to the original creation time
        $this->assertEqualsWithDelta(
            strtotime(substr($customerTransfer->getCreatedAt(), 0, 19)),
            strtotime(substr($restCustomersAttributesTransfer->getCreatedAt(), 0, 19)),
            5, // Allow up to 5 seconds difference for test execution time
            'createdAt timestamp should be within acceptable range',
        );
        // updatedAt should be equal to or after createdAt
        $this->assertGreaterThanOrEqual(
            strtotime(substr($restCustomersAttributesTransfer->getCreatedAt(), 0, 19)),
            strtotime(substr($restCustomersAttributesTransfer->getUpdatedAt(), 0, 19)),
            'updatedAt should be equal to or after createdAt',
        );
        $this->assertSame(
            $customerTransfer->getDateOfBirth(),
            $restCustomersAttributesTransfer->getDateOfBirth(),
        );
        $this->assertSame(
            $customerTransfer->getGender(),
            $restCustomersAttributesTransfer->getGender(),
        );
        $this->assertSame(
            $customerTransfer->getSalutation(),
            $restCustomersAttributesTransfer->getSalutation(),
        );
    }
}
