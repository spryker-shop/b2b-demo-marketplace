<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Invoker;

/**
 * Immutable outcome of a Storefront API sub-request: HTTP status plus the decoded response body.
 */
class StorefrontSubRequestResult
{
    /**
     * @var int
     */
    protected const HTTP_STATUS_MULTIPLE_CHOICES = 300;

    /**
     * @var int
     */
    protected const HTTP_STATUS_OK = 200;

    /**
     * @param int $statusCode
     * @param array<mixed> $payload
     * @param string $rawBody
     */
    public function __construct(
        protected readonly int $statusCode,
        protected readonly array $payload = [],
        protected readonly string $rawBody = '',
    ) {
    }

    /**
     * Specification:
     * - Returns the HTTP status code of the sub-request response.
     *
     * @api
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Specification:
     * - Returns the decoded JSON response body, or an empty array when the body was not JSON.
     *
     * @api
     *
     * @return array<mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * Specification:
     * - Returns the undecoded response body.
     *
     * @api
     */
    public function getRawBody(): string
    {
        return $this->rawBody;
    }

    /**
     * Specification:
     * - Returns true when the sub-request completed with a 2xx status code.
     *
     * @api
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= static::HTTP_STATUS_OK
            && $this->statusCode < static::HTTP_STATUS_MULTIPLE_CHOICES;
    }
}
