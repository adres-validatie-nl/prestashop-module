<?php

namespace PrestaShop\Module\AdresValidatie\Form;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

class AccountSettingsFormDataProvider implements FormDataProviderInterface
{
    /**
     * @var DataConfigurationInterface
     */
    private $accountSettingsDataConfiguration;

    public function __construct(DataConfigurationInterface $accountSettingsDataConfiguration)
    {
        $this->accountSettingsDataConfiguration = $accountSettingsDataConfiguration;
    }

    public function getData(): array
    {
        return $this->accountSettingsDataConfiguration->getConfiguration();
    }

    public function setData(array $data): array
    {
        return $this->accountSettingsDataConfiguration->updateConfiguration($data);
    }
}