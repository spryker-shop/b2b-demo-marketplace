<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\ShopConfiguration\Communication\Form;

use Generated\Shared\Transfer\FileSystemContentTransfer;
use Generated\Shared\Transfer\FileTransfer;
use Spryker\Zed\FileManagerGui\Communication\File\UploadedFile;
use Spryker\Zed\Kernel\Communication\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

/**
 * @method \Pyz\Zed\ShopConfiguration\Communication\ShopConfigurationCommunicationFactory getFactory()
 */
class StoreConfigurationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addLogoField($builder)
            ->addFavicon($builder)
            ->addShopName($builder)
            ->addShopDomain($builder)
            ->addDefaultTitle($builder)
            ->addDefaultDescription($builder)
            ->addDefaultKeyWords($builder)
            ->addGoogleAnalyticsName($builder)
            ->addPrimaryColor($builder)
            ->addSecondaryColor($builder)
            ->addButtonHoverColor($builder)
            ->addHeaderBackground($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }

    protected function addLogoField(FormBuilderInterface $builder): self
    {
        $builder->add('logo', FileType::class, [
            'label' => 'Store Logo URL',
            'required' => false,
            'mapped' => false,
            'attr' => [
                'placeholder' => 'Enter url to the logo image',
                'class' => 'form-control',
            ],
            'constraints' => [
                new Image([
                    'maxSize' => '5M',
                    'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'],
                    'detectCorrupted' => true,
                ]),
            ],
        ]);

        return $this;
    }

    protected function addFavicon(FormBuilderInterface $builder): self
    {
        $builder->add('favicon', FileType::class, [
            'label' => 'Favicon URL',
            'required' => false,
            'mapped' => false,
            'attr' => [
                'placeholder' => 'Enter favicon url',
                'class' => 'form-control',
            ],
            'constraints' => [
                new Image([
                    'maxSize' => '5M',
                    'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp', 'image/ico', 'image/svg+xml'],
                    'detectCorrupted' => true,
                ]),
            ],
        ]);

        return $this;
    }

    protected function addShopName(FormBuilderInterface $builder): self
    {
        $builder->add('shop_name', TextType::class, [
            'label' => 'Shop Name',
            'required' => false,
            'attr' => [
                'placeholder' => 'Enter Shop Name',
                'class' => 'form-control',
            ],
        ]);

        return $this;
    }

    protected function addShopDomain(FormBuilderInterface $builder): self
    {
        $builder->add('shop_domain', TextType::class, [
            'label' => 'Shop Sub-Domain',
            'required' => true,
            'attr' => [
                'placeholder' => 'Enter Shop Sub-Domain',
                'class' => 'form-control',
            ],
        ]);

        return $this;
    }

    protected function addDefaultTitle(FormBuilderInterface $builder): self
    {
        $builder->add('default_title', TextType::class, [
            'label' => 'Default title',
            'required' => false,
            'attr' => [
                'placeholder' => 'Enter Default Title',
                'class' => 'form-control',
            ],
        ]);

        return $this;
    }

    protected function addDefaultDescription(FormBuilderInterface $builder): self
    {
        $builder->add('default_description', TextareaType::class, [
            'label' => 'Default Description',
            'required' => false,
            'attr' => [
                'placeholder' => 'Enter Default Description',
                'class' => 'form-control',
            ],
        ]);

        return $this;
    }

    protected function addDefaultKeyWords(FormBuilderInterface $builder): self
    {
        $builder->add('default_keywords', TextType::class, [
            'label' => 'Default Keywords',
            'required' => false,
            'attr' => [
                'placeholder' => 'Enter Default Keywords',
                'class' => 'form-control',
            ],
        ]);

        return $this;
    }

    protected function addGoogleAnalyticsName(FormBuilderInterface $builder): self
    {
        $builder->add('google_analytics', TextareaType::class, [
            'label' => 'Google Analytics Script',
            'required' => false,
            'attr' => [
                'placeholder' => 'Place your Google Analytics script here',
                'class' => 'form-control',
            ],
        ]);

        return $this;
    }

    protected function addPrimaryColor(FormBuilderInterface $builder): self
    {
        $builder->add('primary_color', TextType::class, [
            'label' => 'Primary Color',
            'required' => false,
            'attr' => [
                'placeholder' => 'Enter Primary Color',
                'class' => 'form-control color-picker',
            ],
        ]);

        return $this;
    }

    protected function addSecondaryColor(FormBuilderInterface $builder): self
    {
        $builder->add('secondary_color', TextType::class, [
            'label' => 'Secondary Color',
            'required' => false,
            'attr' => [
                'placeholder' => 'Enter Secondary Color',
                'class' => 'form-control color-picker',
            ],
        ]);

        return $this;
    }

    protected function addButtonHoverColor(FormBuilderInterface $builder): self
    {
        $builder->add('button_hover_color', TextType::class, [
            'label' => 'Button Hover Color',
            'required' => false,
            'attr' => [
                'placeholder' => 'Enter Button Hover Color',
                'class' => 'form-control color-picker',
            ],
        ]);

        return $this;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     *
     * @return $this
     */
    protected function addHeaderBackground(FormBuilderInterface $builder): self
    {
        $builder->add('header_background', TextType::class, [
            'label' => 'Header Background',
            'required' => false,
            'attr' => [
                'placeholder' => 'Enter Header Background',
                'class' => 'form-control color-picker',
            ],
        ]);

        return $this;
    }
}
