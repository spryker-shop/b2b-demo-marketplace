<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement;

use Generated\Shared\Transfer\CompanyUserTransfer;
use ReflectionProperty;
use Spryker\Zed\CompanyMailConnector\Business\CompanyMailConnectorBusinessFactory;
use Spryker\Zed\CompanyMailConnector\CompanyMailConnectorDependencyProvider;
use Spryker\Zed\CompanyMailConnector\Dependency\Facade\CompanyMailConnectorToMailFacadeInterface;
use Spryker\Zed\CompanyRole\Persistence\CompanyRoleRepository;
use SprykerTest\ApiPlatform\Test\BackendApiTestCase;
use SprykerTest\ApiPlatform\Test\JsonApiResponseAssertionsTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * CXM-specific arrangement for the backend integration suite. Everything about the JSON:API envelope
 * itself lives in {@see JsonApiResponseAssertionsTrait}, which is shared with every other API
 * Platform suite; what remains here is the customer domain and this suite's actor.
 *
 * A base class rather than a trait: every subclass needs the same `$tester` type declaration, and a
 * trait cannot carry the property type Codeception's actor injection relies on.
 */
abstract class AbstractCustomerExperienceManagementBackendApiTestCase extends BackendApiTestCase
{
    use JsonApiResponseAssertionsTrait;

    protected const string UNKNOWN_CUSTOMER_REFERENCE = 'DE--cxm-backend-api-does-not-exist';

    protected const string ATTRIBUTE_STATUS = 'status';

    protected const string ATTRIBUTE_IS_ACTIVE = 'isActive';

    protected const string COMPANY_STATUS_PENDING = 'pending';

    protected const string COMPANY_STATUS_APPROVED = 'approved';

    protected const string COMPANY_STATUS_DENIED = 'denied';

    protected const string UNKNOWN_UUID = '11111111-2222-3333-4444-555555555555';

    /**
     * @uses \Spryker\ApiPlatform\EventSubscriber\GlueApiExceptionSubscriber::ERROR_CODE_VALIDATION
     */
    protected const string RESPONSE_CODE_FRAMEWORK_VALIDATION = '901';

    protected const string ATTRIBUTE_PAGINATION = 'pagination';

    protected const string JSON_API_KEY_META = 'meta';

    /**
     * @uses \Spryker\ApiPlatform\ResponseTransform\PaginationLinksTransform::META_PAGINATION
     */
    protected const string META_PAGINATION = 'pagination';

    protected const string PAGINATION_KEY_NUM_FOUND = 'numFound';

    protected const string PAGINATION_KEY_MAX_PAGE = 'maxPage';

    protected const string PAGINATION_KEY_CURRENT_PAGE = 'currentPage';

    protected const string PAGINATION_KEY_CURRENT_ITEMS_PER_PAGE = 'currentItemsPerPage';

    protected const string MALFORMED_JSON_BODY = '{"data":{"type":"companies","attributes":{';

    protected const string LINK_NEXT = 'next';

    /**
     * `UNKNOWN_UUID` is not RFC 4122 well formed, so in a request body the `Uuid` constraint rejects
     * it before any resolver runs. Reaching a resolver's own not-found rejection needs a uuid that
     * is well formed and still matches nothing.
     */
    protected const string UNKNOWN_WELL_FORMED_UUID = '11111111-2222-4333-8444-555555555555';

    protected const string MALFORMED_UUID = 'not-a-uuid';

    protected const string HEADER_ACCEPT_LANGUAGE = 'Accept-Language';

    protected const string LANGUAGE_ENGLISH = 'en';

    /**
     * @uses \Spryker\Zed\CompanyRole\Business\CompanyUserValidator\CompanyUserRoleValidator::GLOSSARY_KEY_ERROR_ROLE_NOT_IN_COMPANY
     */
    protected const string ERROR_MESSAGE_KEY_ROLE_NOT_IN_COMPANY = 'message.company_user.validation.role_not_in_company';

    /**
     * Any error detail still carrying this prefix is an untranslated glossary key.
     */
    protected const string GLOSSARY_KEY_PREFIX = 'message.';

    protected const string ATTRIBUTE_CUSTOMER_REFERENCE = 'customerReference';

    protected const string ATTRIBUTE_COMPANY_UUID = 'companyUuid';

    protected const string ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID = 'companyBusinessUnitUuid';

    protected const string ATTRIBUTE_COMPANY_ROLE_UUIDS = 'companyRoleUuids';

    protected const string ATTRIBUTE_CUSTOMER = 'customer';

    protected const string IGNORED_FIRST_NAME = 'ShouldBeIgnored';

    protected CustomerExperienceManagementBackendApiIntegrationTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stubCompanyMailConnectorMailFacade();
    }

    protected function stubCompanyMailConnectorMailFacade(): void
    {
        $this->tester->setDependency(
            CompanyMailConnectorDependencyProvider::FACADE_MAIL,
            $this->createMock(CompanyMailConnectorToMailFacadeInterface::class),
            CompanyMailConnectorBusinessFactory::class,
        );
    }

    protected function resetCompanyRoleCollectionCache(): void
    {
        $reflectionProperty = new ReflectionProperty(CompanyRoleRepository::class, 'companyRoleCollectionCache');
        $reflectionProperty->setValue(null, []);
    }

    /**
     * One company user in its own company. Use this whenever the test acts on a single company user;
     * {@see static::haveTwoListedCompanyUsers()} additionally provisions a second business unit,
     * customer and company user, which only collection and default-switching tests need.
     */
    protected function haveListedCompanyUser(): CompanyUserTransfer
    {
        $companyContext = $this->tester->haveCompanyContext();

        return $this->tester->haveCompanyUserFor(
            $companyContext['company'],
            $companyContext['businessUnit'],
            $companyContext['role'],
        );
    }

    /**
     * @return array{0: \Generated\Shared\Transfer\CompanyUserTransfer, 1: \Generated\Shared\Transfer\CompanyUserTransfer, 2: string}
     */
    protected function haveTwoListedCompanyUsers(): array
    {
        $companyContext = $this->tester->haveCompanyContext();
        $listedLastName = $this->tester->getListedCompanyUserLastName();

        [$firstCompanyUserTransfer, $secondCompanyUserTransfer] = $this->tester->haveTwoListedCompanyUsers(
            $companyContext['company'],
            $companyContext['businessUnit'],
            $companyContext['role'],
            $listedLastName,
        );

        return [$firstCompanyUserTransfer, $secondCompanyUserTransfer, $listedLastName];
    }

    protected function anonymizeCustomerOfCompanyUser(CompanyUserTransfer $companyUserTransfer): void
    {
        $response = $this->handleApiRequest(
            'DELETE',
            $this->tester->getCustomerUrl($companyUserTransfer->getCustomerOrFail()->getCustomerReferenceOrFail()),
        );

        $this->assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode(),
            sprintf('Could not anonymize the customer under test: %s', (string)$response->getContent()),
        );
    }

    /**
     * @return array<string>
     */
    protected function getCompanyRoleUuids(CompanyUserTransfer $companyUserTransfer): array
    {
        $companyRoleUuids = [];

        foreach ($companyUserTransfer->getCompanyRoleCollection()?->getRoles() ?? [] as $companyRoleTransfer) {
            $companyRoleUuids[] = $companyRoleTransfer->getUuidOrFail();
        }

        return $companyRoleUuids;
    }

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

    /**
     * @param array<string, mixed> $attributes
     *
     * @return non-empty-string
     */
    protected function haveCompanyViaApi(array $attributes = []): string
    {
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyCollectionUrl(),
            $this->tester->buildCompanyRequestBody($this->tester->buildValidCompanyAttributes($attributes)),
        );

        $this->assertSame(
            Response::HTTP_CREATED,
            $response->getStatusCode(),
            sprintf('Could not provision the company under test: %s', (string)$response->getContent()),
        );

        $uuid = (string)($this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');
        $this->assertNotSame('', $uuid, 'The created company must be addressable by uuid.');

        /** @var non-empty-string $uuid */
        return $uuid;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function haveAddressViaApi(string $customerReference, array $attributes = []): string
    {
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerAddressCollectionUrl($customerReference),
            $this->tester->buildCustomerAddressRequestBody(
                $this->tester->buildValidCustomerAddressAttributes($attributes),
            ),
        );

        $this->assertSame(
            Response::HTTP_CREATED,
            $response->getStatusCode(),
            sprintf('Could not provision the address under test: %s', (string)$response->getContent()),
        );

        return (string)($this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function haveNoteViaApi(string $customerReference, array $attributes = []): string
    {
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerNoteCollectionUrl($customerReference),
            $this->tester->buildCustomerNoteRequestBody(
                $this->tester->buildValidCustomerNoteAttributes($attributes),
            ),
        );

        $this->assertSame(
            Response::HTTP_CREATED,
            $response->getStatusCode(),
            sprintf('Could not provision the note under test: %s', (string)$response->getContent()),
        );

        return (string)($this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');
    }
}
