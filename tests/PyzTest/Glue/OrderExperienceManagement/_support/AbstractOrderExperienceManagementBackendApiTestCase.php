<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\OrderExperienceManagement;

use SprykerTest\ApiPlatform\Test\BackendApiTestCase;
use SprykerTest\ApiPlatform\Test\JsonApiResponseAssertionsTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sales-order-specific arrangement for the backend integration suite. Everything about the JSON:API
 * envelope itself lives in {@see JsonApiResponseAssertionsTrait}, which is shared with every other
 * API Platform suite; what remains here is this domain and this suite's actor.
 *
 * A base class rather than a trait: every subclass needs the same `$tester` type declaration, and a
 * trait cannot carry the property type Codeception's actor injection relies on.
 */
abstract class AbstractOrderExperienceManagementBackendApiTestCase extends BackendApiTestCase
{
    use JsonApiResponseAssertionsTrait;

    /**
     * Shaped like a real reference (`<store>--<number>`) so it exercises the lookup rather than
     * being rejected earlier as malformed.
     */
    protected const string UNKNOWN_ORDER_REFERENCE = 'DE--oem-backend-api-does-not-exist';

    /**
     * The OMS process the fixture orders are placed into. `Test01` ships with the Oms module's own
     * test state machine, which {@see \SprykerTest\Zed\Oms\Helper\OmsHelper::configureTestStateMachine()}
     * points the OMS config at — the project's real processes are not needed to read an order back.
     */
    protected const string OMS_PROCESS_NAME = 'Test01';

    /**
     * Collection metadata is reported as an attribute of each member rather than in the document's
     * `meta`.
     *
     * @uses \SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Provider\OrdersBackendProvider
     */
    protected const string ATTRIBUTE_PAGINATION = 'pagination';

    protected const string PAGINATION_KEY_NUM_FOUND = 'numFound';

    protected const string LINK_NEXT = 'next';

    protected OrderExperienceManagementBackendApiIntegrationTester $tester;

    /**
     * POSTs to `/orders` and registers cleanup for the order if one was actually placed.
     *
     * Every POST goes through here rather than calling `handleApiRequest()` directly, because a
     * successful placement is not undone by the suite's transaction rollback — see
     * {@see \PyzTest\Glue\OrderExperienceManagement\Helper\OrderExperienceManagementBackendApiHelper::cleanupPlacedOrder()}.
     * Rejected and unauthorized responses carry no id, so the registration is simply skipped.
     *
     * @param array<string, mixed> $attributes
     */
    protected function createOrderViaApi(array $attributes): Response
    {
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getOrderCollectionUrl(),
            $this->tester->buildOrderRequestBody($attributes),
        );

        $orderReference = (string)($this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');

        if ($orderReference !== '') {
            $this->tester->cleanupPlacedOrder($orderReference);
        }

        return $response;
    }
}
