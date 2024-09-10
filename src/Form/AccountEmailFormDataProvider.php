<?php

namespace PrestaShop\Module\AdresValidatie\Form;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

class AccountEmailFormDataProvider implements FormDataProviderInterface
{
    /**
     * @var DataConfigurationInterface
     */
    private $accountEmailDataConfiguration;

    public function __construct(DataConfigurationInterface $emailDataConfiguration)
    {
        $this->accountEmailDataConfiguration = $emailDataConfiguration;
    }

    public function getData(): array
    {
        return $this->accountEmailDataConfiguration->getConfiguration();
    }

    public function setData(array $data): array
    {
        return $this->accountEmailDataConfiguration->updateConfiguration($data);
    }
}