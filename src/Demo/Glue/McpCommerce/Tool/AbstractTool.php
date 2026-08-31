<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Tool;

use Demo\Glue\McpCommerce\Invoker\StorefrontSubRequestInvokerInterface;
use Demo\Glue\McpCommerce\Invoker\StorefrontSubRequestResult;
use Generated\Shared\Transfer\McpIdentityTransfer;

/**
 * Shared plumbing for every MCP tool: turning the validated MCP identity into the Storefront identity
 * claims the invoker expects, and reading the JSON:API envelope the Storefront resources return.
 *
 * The claim names are the ones the Storefront identity subscribers read, so a tool never has to know
 * how identity reaches a resource — only which resource to call.
 */
abstract class AbstractTool implements ToolInterface
{
    /**
     * @var string
     */
    protected const CLAIM_CUSTOMER_REFERENCE = 'customer_reference';

    /**
     * @var string
     */
    protected const CLAIM_ID_CUSTOMER = 'id_customer';

    /**
     * @var string
     */
    protected const CLAIM_SCOPE = 'scope';

    /**
     * @var string
     */
    protected const METHOD_GET = 'GET';

    /**
     * @var string
     */
    protected const METHOD_POST = 'POST';

    /**
     * @var string
     */
    protected const PAYLOAD_KEY_DATA = 'data';

    /**
     * @var string
     */
    protected const PAYLOAD_KEY_ATTRIBUTES = 'attributes';

    /**
     * @var string
     */
    protected const PAYLOAD_KEY_INCLUDED = 'included';

    /**
     * @var string
     */
    protected const PAYLOAD_KEY_TYPE = 'type';

    /**
     * @var string
     */
    protected const PAYLOAD_KEY_ID = 'id';

    /**
     * @var string
     */
    protected const PAYLOAD_KEY_ERRORS = 'errors';

    /**
     * @var string
     */
    protected const PAYLOAD_KEY_DETAIL = 'detail';

    /**
     * @var string
     */
    protected const SCHEMA_KEY_TYPE = 'type';

    /**
     * @var string
     */
    protected const SCHEMA_KEY_PROPERTIES = 'properties';

    /**
     * @var string
     */
    protected const SCHEMA_KEY_REQUIRED = 'required';

    /**
     * @var string
     */
    protected const SCHEMA_KEY_DESCRIPTION = 'description';

    /**
     * @var string
     */
    protected const SCHEMA_TYPE_OBJECT = 'object';

    /**
     * @var string
     */
    protected const SCHEMA_TYPE_STRING = 'string';

    /**
     * @var string
     */
    protected const SCHEMA_TYPE_INTEGER = 'integer';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_GENERIC_FAILURE = 'The shop could not complete this request.';

    public function __construct(
        protected readonly StorefrontSubRequestInvokerInterface $storefrontSubRequestInvoker,
    ) {
    }

    /**
     * Maps the validated MCP identity onto the OAuth identity claims the Storefront identity
     * subscribers consume. No shop access or refresh token is involved at any point.
     *
     * @return array<string, mixed>
     */
    protected function createIdentityClaims(McpIdentityTransfer $mcpIdentityTransfer): array
    {
        $identityClaims = [
            static::CLAIM_CUSTOMER_REFERENCE => (string)$mcpIdentityTransfer->getCustomerReference(),
        ];

        if ($mcpIdentityTransfer->getIdCustomer() !== null) {
            $identityClaims[static::CLAIM_ID_CUSTOMER] = $mcpIdentityTransfer->getIdCustomer();
        }

        if ($mcpIdentityTransfer->getScopes() !== null) {
            $identityClaims[static::CLAIM_SCOPE] = $mcpIdentityTransfer->getScopes();
        }

        return $identityClaims;
    }

    /**
     * Extracts the first JSON:API error detail so a tool error can name the actual reason the shop
     * rejected the call instead of a generic failure.
     */
    protected function extractErrorMessage(StorefrontSubRequestResult $storefrontSubRequestResult): string
    {
        $errors = $storefrontSubRequestResult->getPayload()[static::PAYLOAD_KEY_ERRORS] ?? null;

        if (!is_array($errors)) {
            return static::ERROR_MESSAGE_GENERIC_FAILURE;
        }

        foreach ($errors as $error) {
            if (is_array($error) && is_string($error[static::PAYLOAD_KEY_DETAIL] ?? null)) {
                return $error[static::PAYLOAD_KEY_DETAIL];
            }
        }

        return static::ERROR_MESSAGE_GENERIC_FAILURE;
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractResourceAttributes(StorefrontSubRequestResult $storefrontSubRequestResult): array
    {
        $data = $storefrontSubRequestResult->getPayload()[static::PAYLOAD_KEY_DATA] ?? null;

        if (!is_array($data)) {
            return [];
        }

        $attributes = $this->isResourceCollection($data)
            ? ($data[0][static::PAYLOAD_KEY_ATTRIBUTES] ?? null)
            : ($data[static::PAYLOAD_KEY_ATTRIBUTES] ?? null);

        return is_array($attributes) ? $attributes : [];
    }

    /**
     * @param array<mixed> $data
     */
    protected function isResourceCollection(array $data): bool
    {
        return array_key_exists(0, $data);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function extractResourceCollection(StorefrontSubRequestResult $storefrontSubRequestResult): array
    {
        $data = $storefrontSubRequestResult->getPayload()[static::PAYLOAD_KEY_DATA] ?? null;

        if (!is_array($data) || !$this->isResourceCollection($data)) {
            return [];
        }

        return array_values(array_filter($data, 'is_array'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function extractIncludedByType(
        StorefrontSubRequestResult $storefrontSubRequestResult,
        string $type,
    ): array {
        $included = $storefrontSubRequestResult->getPayload()[static::PAYLOAD_KEY_INCLUDED] ?? null;

        if (!is_array($included)) {
            return [];
        }

        $matchingResources = array_filter(
            $included,
            static fn ($resource): bool => is_array($resource)
                && ($resource[self::PAYLOAD_KEY_TYPE] ?? null) === $type,
        );

        return array_values($matchingResources);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    protected function readStringArgument(array $arguments, string $key): string
    {
        $value = $arguments[$key] ?? null;

        return is_string($value) || is_int($value) ? trim((string)$value) : '';
    }

    /**
     * @param array<string, mixed> $arguments
     */
    protected function readIntegerArgument(array $arguments, string $key, int $default): int
    {
        $value = $arguments[$key] ?? null;

        return is_numeric($value) ? (int)$value : $default;
    }

    /**
     * Returns the default only when the argument is genuinely ABSENT, and null when it is present but
     * not a whole number. Callers must distinguish the two: silently coercing a non-numeric value to
     * the default makes the tool do something the AI client never asked for (e.g. `quantity: "abc"`
     * quietly becoming 1).
     *
     * @param array<string, mixed> $arguments
     * @param string $key
     * @param int $default
     *
     * @return int|null
     */
    protected function findIntegerArgument(array $arguments, string $key, int $default): ?int
    {
        if (!array_key_exists($key, $arguments) || $arguments[$key] === null) {
            return $default;
        }

        $value = $arguments[$key];

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1) {
            return (int)trim($value);
        }

        return null;
    }
}
