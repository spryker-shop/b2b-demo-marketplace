<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockCustomerGroup\Business\Validator;

use Generated\Shared\Transfer\CmsBlockValidationRequestTransfer;
use Generated\Shared\Transfer\CmsBlockValidationResponseTransfer;

class CmsBlockValidator implements CmsBlockValidatorInterface
{
    /**
     * @param array<\Demo\Zed\CmsBlockCustomerGroup\Business\Validator\CmsBlockValidatorInterface> $cmsBlockValidators
     */
    public function __construct(protected array $cmsBlockValidators)
    {
    }

    public function validate(
        CmsBlockValidationRequestTransfer $cmsBlockValidationRequestTransfer,
        ?CmsBlockValidationResponseTransfer $cmsBlockValidationResponseTransfer = null,
    ): CmsBlockValidationResponseTransfer {
        $cmsBlockValidationResponseTransfer = $cmsBlockValidationResponseTransfer ?? new CmsBlockValidationResponseTransfer();
        $isValid = true;

        foreach ($this->cmsBlockValidators as $cmsBlockValidator) {
            $childCmsBlockValidationResponseTransfer = $cmsBlockValidator->validate(
                $cmsBlockValidationRequestTransfer,
                new CmsBlockValidationResponseTransfer(),
            );

            $isValid = $isValid && (bool)$childCmsBlockValidationResponseTransfer->getIsValid();
        }

        return $cmsBlockValidationResponseTransfer->setIsValid($isValid);
    }
}
