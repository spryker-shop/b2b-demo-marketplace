<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Security;

use Generated\Shared\Transfer\McpIdentityTransfer;

interface McpCustomerIdentityReaderInterface
{
    /**
     * Specification:
     * - Authenticates a customer with the existing shop email and password flow.
     * - Returns the resolved `customerReference` and `idCustomer` claims only.
     * - Discards the minted shop access and refresh tokens without storing, logging or returning them.
     * - Returns null when the credentials are rejected or the claims cannot be resolved.
     *
     * @param string $email
     * @param string $password
     *
     * @return \Generated\Shared\Transfer\McpIdentityTransfer|null
     */
    public function findIdentityByCredentials(string $email, string $password): ?McpIdentityTransfer;
}
