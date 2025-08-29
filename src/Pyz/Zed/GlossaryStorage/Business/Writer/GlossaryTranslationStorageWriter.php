<?php

namespace Pyz\Zed\GlossaryStorage\Business\Writer;

use Generated\Shared\Transfer\SpyGlossaryStorageEntityTransfer;
use Generated\Shared\Transfer\SpyGlossaryTranslationEntityTransfer;

class GlossaryTranslationStorageWriter extends \Spryker\Zed\GlossaryStorage\Business\Writer\GlossaryTranslationStorageWriter
{
    /**
     * @param \Generated\Shared\Transfer\SpyGlossaryTranslationEntityTransfer $glossaryTranslationEntityTransfer
     * @param \Generated\Shared\Transfer\SpyGlossaryStorageEntityTransfer|null $glossaryStorageEntityTransfer
     *
     * @return \Generated\Shared\Transfer\SpyGlossaryStorageEntityTransfer
     */
    protected function storeDataSet(
        SpyGlossaryTranslationEntityTransfer $glossaryTranslationEntityTransfer,
        ?SpyGlossaryStorageEntityTransfer $glossaryStorageEntityTransfer = null
    ) {
        if ($glossaryStorageEntityTransfer === null) {
            $glossaryStorageEntityTransfer = new SpyGlossaryStorageEntityTransfer();
        }

        $glossaryStorageEntityTransfer->setFkGlossaryKey($glossaryTranslationEntityTransfer->getFkGlossaryKey());
        $glossaryStorageEntityTransfer->setGlossaryKey($glossaryTranslationEntityTransfer->getGlossaryKey()->getKey());
        $glossaryStorageEntityTransfer->setLocale($glossaryTranslationEntityTransfer->getLocale()->getLocaleName());
        $glossaryStorageEntityTransfer->setIdTenant($glossaryTranslationEntityTransfer->getIdTenant());

        /*
         * This line added to keep the glossary data structure in backward compatible and
         * will be removed in the next major version.
         */
        $data = $this->makeGlossaryDataBackwardCompatible($glossaryTranslationEntityTransfer->modifiedToArray());
        $glossaryStorageEntityTransfer->setData(json_encode($data) ?: null);

        return $glossaryStorageEntityTransfer;
    }
}
