<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\Merchant;

use Spryker\Glue\Merchant\MerchantConfig as MerchantApiConfig;
use SprykerTest\ApiPlatform\Test\BackendApiTestCase;
use SprykerTest\ApiPlatform\Test\JsonApiResponseAssertionsTrait;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractMerchantBackendApiTestCase extends BackendApiTestCase
{
    use JsonApiResponseAssertionsTrait;

    protected const string UNKNOWN_MERCHANT_REFERENCE = 'MER-does-not-exist';

    protected const string UNSUPPORTED_STATUS = 'not-a-merchant-status';

    protected const string UPDATED_NAME = 'MerchantBackendApi Updated';

    protected const string RESPONSE_CODE_MERCHANT_NOT_FOUND = MerchantApiConfig::RESPONSE_CODE_MERCHANT_NOT_FOUND;

    protected const string RESPONSE_CODE_INVALID_SORT_FIELD = MerchantApiConfig::RESPONSE_CODE_INVALID_SORT_FIELD;

    protected const string RESPONSE_CODE_UNKNOWN_STORE = MerchantApiConfig::RESPONSE_CODE_UNKNOWN_STORE;

    protected const string RESPONSE_CODE_UNKNOWN_LOCALE = MerchantApiConfig::RESPONSE_CODE_UNKNOWN_LOCALE;

    protected const string RESPONSE_CODE_MERCHANT_VALIDATION = MerchantApiConfig::RESPONSE_CODE_VALIDATION;

    protected const string RESPONSE_CODE_MISSING_MERCHANT_URL_LOCALE = MerchantApiConfig::RESPONSE_CODE_MISSING_MERCHANT_URL_LOCALE;

    protected const string RESPONSE_CODE_MULTIPLE_SORT_FIELDS = MerchantApiConfig::RESPONSE_CODE_MULTIPLE_SORT_FIELDS;

    protected const string RESPONSE_CODE_UNKNOWN_FILTER = MerchantApiConfig::RESPONSE_CODE_UNKNOWN_FILTER;

    protected const string RESPONSE_CODE_INVALID_FILTER_VALUE = MerchantApiConfig::RESPONSE_CODE_INVALID_FILTER_VALUE;

    protected const string RESPONSE_CODE_UNKNOWN_STATUS = MerchantApiConfig::RESPONSE_CODE_UNKNOWN_STATUS;

    protected const string ATTRIBUTE_STATUS = 'status';

    protected const string ATTRIBUTE_IS_ACTIVE = 'isActive';

    protected const string ATTRIBUTE_NAME = 'name';

    protected const string ATTRIBUTE_MERCHANT_REFERENCE = 'merchantReference';

    protected const string ATTRIBUTE_EMAIL = 'email';

    protected const string ATTRIBUTE_REGISTRATION_NUMBER = 'registrationNumber';

    protected const string ATTRIBUTE_STORES = 'stores';

    protected const string ATTRIBUTE_MERCHANT_URLS = 'merchantUrls';

    protected const string ATTRIBUTE_WAREHOUSES = 'warehouses';

    protected const string ATTRIBUTE_IS_OPEN_FOR_RELATION_REQUEST = 'isOpenForRelationRequest';

    protected const string ATTRIBUTE_PAGINATION = 'pagination';

    protected const string JSON_API_KEY_META = 'meta';

    protected const string META_PAGINATION = 'pagination';

    protected const string PAGINATION_KEY_NUM_FOUND = 'numFound';

    protected MerchantBackendApiIntegrationTester $tester;

    /**
     * @return array<string, mixed>
     */
    protected function getMetaPagination(Response $response): array
    {
        $meta = $this->decodeJsonApi($response)[static::JSON_API_KEY_META] ?? [];
        $this->assertIsArray($meta[static::META_PAGINATION] ?? null, 'A backend collection must carry top-level meta.pagination.');
        $this->assertArrayNotHasKey(
            static::ATTRIBUTE_PAGINATION,
            $this->getFirstResourceAttributes($response),
            'Pagination must not be duplicated as a resource attribute.',
        );

        return $meta[static::META_PAGINATION];
    }
}
