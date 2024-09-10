<?php

declare(strict_types=1);

namespace PrestaShop\Module\AdresValidatie\Form;

use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;

class AccountEmailFormType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => $this->trans('Email', 'Modules.AdresValidatie.Admin'),
                'help' => $this->trans('The email adress for your adres-validatie.nl account.', 'Modules.AdresValidatie.Admin'),
            ]);
    }
}