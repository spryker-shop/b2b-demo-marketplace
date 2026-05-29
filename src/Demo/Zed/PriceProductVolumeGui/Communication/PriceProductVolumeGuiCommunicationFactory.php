<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductVolumeGui\Communication;

use Demo\Zed\PriceProductVolumeGui\Communication\Form\PriceVolumeCollectionFormType;
use Spryker\Zed\PriceProductVolumeGui\Communication\PriceProductVolumeGuiCommunicationFactory as SprykerPriceProductVolumeGuiCommunicationFactory;
use Symfony\Component\Form\FormInterface;

class PriceProductVolumeGuiCommunicationFactory extends SprykerPriceProductVolumeGuiCommunicationFactory
{
    /**
     * @param array<mixed>|null $data
     * @param array<string, mixed> $options
     */
    public function getPriceVolumeCollectionFormType(?array $data = null, array $options = []): FormInterface
    {
        return $this->getFormFactory()->create(PriceVolumeCollectionFormType::class, $data, $options);
    }
}
