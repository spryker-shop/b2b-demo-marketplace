<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockGui\Communication\Form\Block;

use Generated\Shared\Transfer\CmsBlockTransfer;
use Generated\Shared\Transfer\CustomerGroupCollectionTransfer;
use Generated\Shared\Transfer\CustomerGroupTransfer;
use Spryker\Zed\CmsBlockGui\Communication\Form\Block\CmsBlockForm as SprykerCmsBlockForm;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @method \Demo\Zed\CmsBlockGui\Communication\CmsBlockGuiCommunicationFactory getFactory()
 * @method \Spryker\Zed\CmsBlockGui\CmsBlockGuiConfig getConfig()
 */
class CmsBlockForm extends SprykerCmsBlockForm
{
    public const string OPTION_CUSTOMER_GROUP = 'customerGroups';

    public const string FIELD_IS_RESTRICTED = 'isRestricted';

    public const int IS_RESTRICTED_YES = 1;

    public const int IS_RESTRICTED_NO = 0;

    public const string TRANSLATION_KEY_VISIBILITY_ALL_CUSTOMERS = 'cms_block.visibility.all_customers';

    public const string TRANSLATION_KEY_VISIBILITY_LOGGED_IN_CUSTOMERS = 'cms_block.visibility.logged_in_customers';

    protected const string CSS_CLASS_RADIO_ROW = 'radio_row';

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array<string, mixed> $options
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $this->addCustomerGroups($builder, $options[static::OPTION_CUSTOMER_GROUP]);
        $this->addContentVisibility($builder);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, $this->clearCustomerGroupsWhenNotRestrictedCallback());
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault(static::OPTION_CUSTOMER_GROUP, []);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array<int, string> $choices
     *
     * @return void
     */
    protected function addCustomerGroups(FormBuilderInterface $builder, array $choices): void
    {
        $builder->add(static::OPTION_CUSTOMER_GROUP, ChoiceType::class, [
            'label' => false,
            'choices' => array_flip($choices),
            'multiple' => true,
            'expanded' => true,
        ]);

        $this->addCustomerGroupTransformer($builder);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     *
     * @return void
     */
    protected function addContentVisibility(FormBuilderInterface $builder): void
    {
        $defaultValue = $this->resolveIsRestrictedDefault($builder->getData());

        $builder->add(static::FIELD_IS_RESTRICTED, ChoiceType::class, [
            'expanded' => true,
            'multiple' => false,
            'label' => false,
            'choices' => array_flip([
                static::IS_RESTRICTED_NO => static::TRANSLATION_KEY_VISIBILITY_ALL_CUSTOMERS,
                static::IS_RESTRICTED_YES => static::TRANSLATION_KEY_VISIBILITY_LOGGED_IN_CUSTOMERS,
            ]),
            'choice_translation_domain' => true,
            'data' => $defaultValue ? static::IS_RESTRICTED_YES : static::IS_RESTRICTED_NO,
            'constraints' => [new NotBlank()],
            'attr' => ['class' => static::CSS_CLASS_RADIO_ROW],
        ]);
    }

    protected function resolveIsRestrictedDefault(mixed $defaultData): bool
    {
        if ($defaultData instanceof CmsBlockTransfer) {
            return (bool)$defaultData->getIsRestricted();
        }

        return false;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     *
     * @return void
     */
    protected function addCustomerGroupTransformer(FormBuilderInterface $builder): void
    {
        $builder->get(static::OPTION_CUSTOMER_GROUP)
            ->addModelTransformer(new CallbackTransformer(
                function ($customerGroups) {
                    if (!$customerGroups instanceof CustomerGroupCollectionTransfer) {
                        return [];
                    }

                    $result = [];
                    foreach ($customerGroups->getGroups() as $customerGroup) {
                        $idCustomerGroup = $customerGroup->getIdCustomerGroup();
                        if ($idCustomerGroup === null) {
                            continue;
                        }
                        $result[] = $idCustomerGroup;
                    }

                    return $result;
                },
                function (array $customerGroupIds) {
                    $collection = new CustomerGroupCollectionTransfer();
                    foreach ($customerGroupIds as $idCustomerGroup) {
                        $collection->addGroup((new CustomerGroupTransfer())->setIdCustomerGroup((int)$idCustomerGroup));
                    }

                    return $collection;
                },
            ));
    }

    protected function clearCustomerGroupsWhenNotRestrictedCallback(): callable
    {
        return function (FormEvent $formEvent): void {
            $data = $formEvent->getData();
            if (!is_array($data)) {
                return;
            }

            if ((int)($data[static::FIELD_IS_RESTRICTED] ?? static::IS_RESTRICTED_NO) === static::IS_RESTRICTED_YES) {
                return;
            }

            $data[static::OPTION_CUSTOMER_GROUP] = [];
            $formEvent->setData($data);
        };
    }
}
