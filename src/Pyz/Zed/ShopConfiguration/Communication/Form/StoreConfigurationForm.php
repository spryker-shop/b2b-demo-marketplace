<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\ShopConfiguration\Communication\Form;

use Spryker\Zed\FileManagerGui\Communication\File\UploadedFile;
use Spryker\Zed\Kernel\Communication\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoreConfigurationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addLogoField($builder)
            ->addFavicon($builder)
            ->addShopName($builder)
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
        $builder->add('logo', TextType::class, [
            'label' => 'Store Logo URL',
            'required' => false,
//            'mapped' => false,
            'attr' => [
                'placeholder' => 'Enter url to the logo image',
                'class' => 'form-control',
            ],
        ]);

//        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
//            $form = $event->getForm();
//
//            /** @var UploadedFile|null $file */
//            $file = $form->get('logo')->getData();
//            if (!$file) {
//                return;
//            }
//
//            $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
//            $safe = (new \Symfony\Component\String\Slugger\AsciiSlugger())
//                ->slug($original);
//            $filename = sprintf('%s-%s.%s', $safe, uniqid('', true), $file->guessExtension());
//
//            $file->move(APPLICATION_ROOT_DIR . '/public/Yves/media', $filename);
//
//            $data = (array) $event->getData();
//            $data['logo'] = '/media/' . $filename;
//
//            $event->setData($data);
//        });

        return $this;
    }

    protected function addFavicon(FormBuilderInterface $builder): self
    {
        $builder->add('favicon', TextType::class, [
            'label' => 'Favicon URL',
            'required' => false,
//            'mapped' => false,
            'attr' => [
                'placeholder' => 'Enter favicon url',
                'class' => 'form-control',
            ],
        ]);

//        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
//            $form = $event->getForm();
//
//            /** @var UploadedFile|null $file */
//            $file = $form->get('favicon')->getData();
//            if (!$file) {
//                return;
//            }
//
//            $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
//            $safe = (new \Symfony\Component\String\Slugger\AsciiSlugger())
//                ->slug($original);
//            $filename = sprintf('%s-%s.%s', $safe, uniqid('', true), $file->guessExtension());
//
//            $file->move(APPLICATION_ROOT_DIR . '/public/Yves/media', $filename);
//
//            // Write the public path back into the array data:
//            $data = (array) $event->getData(); // your array model
//            $data['favicon'] = '/media/' . $filename;
//
//            // Make the updated array the form's final data:
//            $event->setData($data);
//        });

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
