<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Communication\Form;

use Spryker\Zed\Gui\Communication\Form\Type\SelectType;
use Spryker\Zed\Kernel\Communication\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class TenantRegistrationForm extends AbstractType
{
    public const FIELD_COMPANY_NAME = 'companyName';
    public const FIELD_TENANT_NAME = 'tenantName';
    public const FIELD_EMAIL = 'email';
    public const FIELD_PASSWORD = 'password';
    public const FIELD_SUBMIT = 'submit';
    public const DATA_SET_TYPE = 'dataSetType';

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addCompanyNameField($builder)
            ->addTenantNameField($builder)
            ->addInitialDataSet($builder)
            ->addEmailField($builder)
            ->addPasswordField($builder)
            ->addSubmitField($builder);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     *
     * @return $this
     */
    protected function addCompanyNameField(FormBuilderInterface $builder): self
    {
        $builder->add(static::FIELD_COMPANY_NAME, TextType::class, [
            'label' => 'Company Name',
            'required' => true,
            'constraints' => [
                new NotBlank(['message' => 'Company name is required']),
                new Length([
                    'min' => 2,
                    'max' => 255,
                    'minMessage' => 'Company name must be at least {{ limit }} characters long',
                    'maxMessage' => 'Company name cannot be longer than {{ limit }} characters',
                ]),
            ],
            'attr' => [
                'placeholder' => 'Enter your company name',
                'class' => 'form-control',
            ],
        ]);

        return $this;
    }

    protected function addInitialDataSet(FormBuilderInterface $builder): self
    {
        $builder->add(static::DATA_SET_TYPE, SelectType::class, [
            'label' => 'Initial Data Set',
            'choices' => [
                'Minimal Demo Data' => 'demo',
                'Full Demo Data' => 'full',
            ],
            'required' => true,
        ]);

        return $this;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     *
     * @return $this
     */
    protected function addTenantNameField(FormBuilderInterface $builder): self
    {
        $builder->add(static::FIELD_TENANT_NAME, TextType::class, [
            'label' => 'Tenant Name',
            'required' => true,
            'constraints' => [
                new NotBlank(['message' => 'Tenant name is required']),
                new Length([
                    'min' => 3,
                    'max' => 50,
                    'minMessage' => 'Tenant name must be at least {{ limit }} characters long',
                    'maxMessage' => 'Tenant name cannot be longer than {{ limit }} characters',
                ]),
                new Regex([
                    'pattern' => '/^[a-z0-9_-]+$/',
                    'message' => 'Tenant name can only contain lowercase letters, numbers, underscores and hyphens',
                ]),
            ],
            'attr' => [
                'placeholder' => 'Enter a unique tenant identifier (e.g., my-company)',
                'class' => 'form-control',
                'data-availability-check' => 'tenant-name',
            ],
        ]);

        return $this;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     *
     * @return $this
     */
    protected function addEmailField(FormBuilderInterface $builder): self
    {
        $builder->add(static::FIELD_EMAIL, EmailType::class, [
            'label' => 'Email Address',
            'required' => true,
            'constraints' => [
                new NotBlank(['message' => 'Email is required']),
                new Email(['message' => 'Please enter a valid email address']),
            ],
            'attr' => [
                'placeholder' => 'Enter your email address',
                'class' => 'form-control',
                'data-availability-check' => 'email',
            ],
        ]);

        return $this;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     *
     * @return $this
     */
    protected function addPasswordField(FormBuilderInterface $builder): self
    {
        $builder->add(static::FIELD_PASSWORD, RepeatedType::class, [
            'type' => PasswordType::class,
            'invalid_message' => 'The password fields must match.',
            'options' => ['attr' => ['class' => 'form-control']],
            'required' => true,
            'first_options' => [
                'label' => 'Password',
                'attr' => ['placeholder' => 'Enter a strong password'],
            ],
            'second_options' => [
                'label' => 'Confirm Password',
                'attr' => ['placeholder' => 'Confirm your password'],
            ],
            'constraints' => [
                new NotBlank(['message' => 'Password is required']),
                new Length([
                    'min' => 12,
                    'minMessage' => 'Password must be at least {{ limit }} characters long',
                ]),
            ],
        ]);

        return $this;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     *
     * @return $this
     */
    protected function addSubmitField(FormBuilderInterface $builder): self
    {
        $builder->add(static::FIELD_SUBMIT, SubmitType::class, [
            'label' => 'Submit Registration',
            'attr' => [
                'class' => 'btn btn-primary btn-lg',
            ],
        ]);

        return $this;
    }
}
