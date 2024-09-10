<?php

declare(strict_types=1);

namespace PrestaShop\Module\AdresValidatie\Form;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use Validate;
use Context;

class AccountEmailDataConfiguration implements DataConfigurationInterface
{
    public const EMAIL_CONFIGURATION_KEY = 'ADRESVALIDATIE_ACCOUNT_EMAIL';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getConfiguration()
    {
        return [
            'email' => $this->configuration->get(static::EMAIL_CONFIGURATION_KEY) ?? Context::getContext()->employee->email,
        ];
    }

    public function updateConfiguration(array $configuration): array
    {
        $errors = [];

        if ($this->validateConfiguration($configuration)) {
            if (Validate::isEmail($configuration['email'])) {
                $this->configuration->set(static::EMAIL_CONFIGURATION_KEY, $configuration['email']);
            } else {
                $errors[] = 'Invalid email address';
            }
        }

        return $errors;
    }

    public function validateConfiguration(array $configuration): bool
    {
        return isset($configuration['email']);
    }
}