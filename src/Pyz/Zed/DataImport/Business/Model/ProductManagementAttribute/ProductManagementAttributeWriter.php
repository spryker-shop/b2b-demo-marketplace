<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\DataImport\Business\Model\ProductManagementAttribute;

use Orm\Zed\Glossary\Persistence\SpyGlossaryKeyQuery;
use Orm\Zed\Glossary\Persistence\SpyGlossaryTranslationQuery;
use Orm\Zed\ProductAttribute\Persistence\SpyProductManagementAttributeQuery;
use Orm\Zed\ProductAttribute\Persistence\SpyProductManagementAttributeValueQuery;
use Orm\Zed\ProductAttribute\Persistence\SpyProductManagementAttributeValueTranslation;
use Pyz\Zed\DataImport\Business\Model\ProductAttributeKey\AddProductAttributeKeysStep;
use Spryker\Shared\ProductAttribute\ProductAttributeConfig;
use Spryker\Zed\DataImport\Business\Exception\DataImportException;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\PublishAwareStep;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use Spryker\Zed\Glossary\Dependency\GlossaryEvents;
use SprykerFeature\Shared\ProductExperienceManagement\ProductExperienceManagementConfig;
use SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementFacadeInterface;

class ProductManagementAttributeWriter extends PublishAwareStep implements DataImportStepInterface
{
    /**
     * @var int
     */
    public const BULK_SIZE = 100;

    /**
     * @var string
     */
    protected const KEY_VISIBILITY = 'visibility';

    /**
     * @param \SprykerFeature\Zed\ProductExperienceManagement\Business\ProductExperienceManagementFacadeInterface|null $productExperienceManagementFacade
     */
    public function __construct(
        protected readonly ?ProductExperienceManagementFacadeInterface $productExperienceManagementFacade = null,
    ) {
    }

    /**
     * @param \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface $dataSet
     *
     * @return void
     */
    public function execute(DataSetInterface $dataSet): void
    {
        $productManagementAttributeEntity = SpyProductManagementAttributeQuery::create()
            ->filterByFkProductAttributeKey($dataSet[AddProductAttributeKeysStep::KEY_TARGET][$dataSet['key']])
            ->findOneOrCreate();

        $visibility = (string)($dataSet[static::KEY_VISIBILITY] ?? '');

        if ($visibility !== '' && $this->productExperienceManagementFacade !== null) {
            $visibility = $this->normalizeVisibility($visibility, $dataSet['key']);
        }

        $productManagementAttributeEntity
            ->setAllowInput($dataSet['allow_input'])
            ->setInputType($dataSet['input_type'])
            ->setVisibility($visibility);

        $productManagementAttributeEntity->save();

        $this->addPublishEvents(
            ProductExperienceManagementConfig::PRODUCT_ATTRIBUTE_PUBLISH,
            $productManagementAttributeEntity->getIdProductManagementAttribute(),
        );

        $productManagementAttributeValueEntityCollection = SpyProductManagementAttributeValueQuery::create()
            ->findByFkProductManagementAttribute($productManagementAttributeEntity->getIdProductManagementAttribute());

        foreach ($productManagementAttributeValueEntityCollection as $productManagementAttributeValueEntity) {
            foreach ($productManagementAttributeValueEntity->getSpyProductManagementAttributeValueTranslations() as $productManagementAttributeValueTranslation) {
                $productManagementAttributeValueTranslation->delete();
            }

            $productManagementAttributeValueEntity->delete();
        }

        $glossaryKey = ProductAttributeConfig::PRODUCT_ATTRIBUTE_GLOSSARY_PREFIX . $dataSet['key'];
        $glossaryKeyEntity = SpyGlossaryKeyQuery::create()
            ->filterByKey($glossaryKey)
            ->findOneOrCreate();

        $glossaryKeyEntity->save();

        foreach ($dataSet[ProductManagementLocalizedAttributesExtractorStep::KEY_LOCALIZED_ATTRIBUTES] as $idLocale => $attributes) {
            $glossaryTranslationEntity = SpyGlossaryTranslationQuery::create()
                ->filterByFkGlossaryKey($glossaryKeyEntity->getIdGlossaryKey())
                ->filterByFkLocale($idLocale)
                ->findOneOrCreate();

            $glossaryTranslationEntity
                ->setValue($attributes['key_translation'])
                ->save();

            $this->addPublishEvents(GlossaryEvents::GLOSSARY_KEY_PUBLISH, $glossaryTranslationEntity->getFkGlossaryKey());

            if (!empty($attributes['value_translations'])) {
                foreach ($attributes['value_translations'] as $value => $translation) {
                    $productManagementAttributeValueEntity = SpyProductManagementAttributeValueQuery::create()
                        ->filterBySpyProductManagementAttribute($productManagementAttributeEntity)
                        ->filterByValue($value)
                        ->findOneOrCreate();

                    $productManagementAttributeValueEntity->save();

                    $productManagementAttributeValueTranslationEntity = new SpyProductManagementAttributeValueTranslation();
                    $productManagementAttributeValueTranslationEntity
                        ->setSpyProductManagementAttributeValue($productManagementAttributeValueEntity)
                        ->setTranslation($translation)
                        ->setFkLocale($idLocale)
                        ->save();
                }

                continue;
            }

            foreach ($attributes['values'] as $value) {
                $productManagementAttributeValueEntity = SpyProductManagementAttributeValueQuery::create()
                    ->filterBySpyProductManagementAttribute($productManagementAttributeEntity)
                    ->filterByValue($value)
                    ->findOneOrCreate();

                $productManagementAttributeValueEntity->save();
            }
        }
    }

    /**
     * @param string $visibility
     * @param string $attributeKey
     *
     * @throws \Spryker\Zed\DataImport\Business\Exception\DataImportException
     *
     * @return string
     */
    protected function normalizeVisibility(string $visibility, string $attributeKey): string
    {
        $visibilityTypes = array_map('trim', explode(',', $visibility));
        $allowedVisibilityTypes = $this->productExperienceManagementFacade->getAvailableVisibilityTypes();
        $allowedVisibilityTypesMap = array_combine(
            array_map('strtolower', $allowedVisibilityTypes),
            $allowedVisibilityTypes,
        );

        $normalized = [];

        foreach ($visibilityTypes as $visibilityType) {
            $canonical = $allowedVisibilityTypesMap[strtolower($visibilityType)] ?? null;
            if ($canonical === null) {
                throw new DataImportException(
                    sprintf(
                        'Invalid visibility type "%s" for attribute "%s". Allowed: %s (or empty).',
                        $visibilityType,
                        $attributeKey,
                        implode(', ', $allowedVisibilityTypes),
                    ),
                );
            }

            $normalized[] = $canonical;
        }

        return implode(',', $normalized);
    }
}
