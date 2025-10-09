<?php

namespace Go\Zed\CmsBlockGui\Communication;

use Go\Zed\CmsBlockGui\Communication\Form\Glossary\CmsBlockGlossaryPlaceholderTranslationForm;

class CmsBlockGuiCommunicationFactory extends \Spryker\Zed\CmsBlockGui\Communication\CmsBlockGuiCommunicationFactory
{
    /**
     * @return string
     */
    public function getCmsBlockGlossaryPlaceholderTranslationFormType()
    {
        return CmsBlockGlossaryPlaceholderTranslationForm::class;
    }
}
