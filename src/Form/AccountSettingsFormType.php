<?php

namespace PrestaShop\Module\AdresValidatie\Form;

use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class AccountSettingsFormType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client_id', TextType::class, [
                'label' => $this->trans('client_id', 'Modules.AdresValidatie.Admin'),
            ])
            ->add('client_secret', TextType::class, [
                'label' => $this->trans('client_secret', 'Modules.AdresValidatie.Admin'),
            ])
            ->add('hmac_secret', TextType::class, [
                'label' => $this->trans('hmac_secret', 'Modules.AdresValidatie.Admin'),
            ])
        ;
    }
}