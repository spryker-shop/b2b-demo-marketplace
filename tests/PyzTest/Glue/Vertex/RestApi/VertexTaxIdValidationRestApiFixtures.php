<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\Vertex\RestApi;

use Generated\Shared\Transfer\CustomerTransfer;
use PyzTest\Glue\Vertex\VertexApiTester;
use SprykerTest\Shared\Testify\Fixtures\FixturesBuilderInterface;
use SprykerTest\Shared\Testify\Fixtures\FixturesContainerInterface;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group Vertex
 * @group RestApi
 * @group VertexTaxIdValidationRestApiFixtures
 * Add your own group annotations below this line
 * @group EndToEnd
 */
class VertexTaxIdValidationRestApiFixtures implements FixturesBuilderInterface, FixturesContainerInterface
{
    protected const TEST_USERNAME = 'UserVertexTaxIdValidationRestApiFixtures';

    protected const TEST_PASSWORD = 'change123';

    protected CustomerTransfer $customerTransfer;

    public function getCustomerTransfer(): CustomerTransfer
    {
        return $this->customerTransfer;
    }

    public function getTestPassword(): string
    {
        return static::TEST_PASSWORD;
    }

    public function buildFixtures(VertexApiTester $I): FixturesContainerInterface
    {
        $this->customerTransfer = $I->haveCustomer([
            CustomerTransfer::USERNAME => static::TEST_USERNAME,
            CustomerTransfer::PASSWORD => static::TEST_PASSWORD,
            CustomerTransfer::NEW_PASSWORD => static::TEST_PASSWORD,
        ]);
        $this->customerTransfer = $I->confirmCustomer($this->customerTransfer);

        return $this;
    }
}
