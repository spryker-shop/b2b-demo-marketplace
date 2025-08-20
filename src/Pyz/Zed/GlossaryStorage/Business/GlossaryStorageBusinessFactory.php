<?php

namespace Pyz\Zed\GlossaryStorage\Business;

use Pyz\Zed\GlossaryStorage\Business\Writer\GlossaryTranslationStorageWriter;

class GlossaryStorageBusinessFactory extends \Spryker\Zed\GlossaryStorage\Business\GlossaryStorageBusinessFactory
{
    /**
     * @return \Spryker\Zed\GlossaryStorage\Business\Writer\GlossaryTranslationStorageWriterInterface
     */
    public function createGlossaryTranslationStorageWriter()
    {
        return new GlossaryTranslationStorageWriter(
            $this->getEventBehaviorFacade(),
            $this->getRepository(),
            $this->getEntityManager(),
            $this->createGlossaryTranslationStorageMapper(),
        );
    }
}
