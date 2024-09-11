<?php

declare(strict_types=1);

namespace PrestaShop\Module\AdresValidatie\Form;

use PrestaShop\Module\AdresValidatie\Service\ConfigurationService;
use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use Validate;
use Context;

class AccountEmailDataConfiguration implements DataConfigurationInterface
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    public function __construct(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    public function getConfiguration()
    {
        return [
            'email' => $this->configurationService->get('account_email') ?: Context::getContext()->employee->email,
        ];
    }

    public function updateConfiguration(array $configuration): array
    {
        $errors = [];

        if ($this->validateConfiguration($configuration)) {
            if (Validate::isEmail($configuration['email'])) {
                $this->configurationService->set('account_email', $configuration['email']);
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