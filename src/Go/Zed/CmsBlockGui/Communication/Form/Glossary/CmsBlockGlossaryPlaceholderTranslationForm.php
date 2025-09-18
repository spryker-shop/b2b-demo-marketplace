<?php

namespace Go\Zed\CmsBlockGui\Communication\Form\Glossary;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class CmsBlockGlossaryPlaceholderTranslationForm extends \Spryker\Zed\CmsBlockGui\Communication\Form\Glossary\CmsBlockGlossaryPlaceholderTranslationForm
{
    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     *
     * @return $this
     */
    protected function addTranslationField(FormBuilderInterface $builder)
    {
        $builder->add(static::FIELD_TRANSLATION, TextareaType::class, [
            'label' => 'Content',
            'attr' => [
                'class' => 'html-editor',
                'data-editor-config' => 'cms',
            ],
            'required' => false,
            'constraints' => [
                $this->getFactory()->createTwigContentConstraint(),
            ],
            'sanitize_xss' => false,
            'allowed_attributes' => ['style', 'xlink:href', 'data-qa', 'data-toggle', 'data-target', 'aria-expanded', 'aria-controls', 'data-toggle-target', 'active-class', 'active-on-touch', 'class-to-toggle', 'trigger-class-name', 'wrap-class-name', 'is-hidden-sm-only', 'data-background-image'],
            'allowed_html_tags' => ['iframe', 'svg', 'use', 'toggler-accordion', 'symbol', 'lazy-image', 'toggler-click', 'noscript'],
        ]);

        return $this;
    }
}
