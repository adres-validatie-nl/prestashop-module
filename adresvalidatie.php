<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

// Prestashop does not consistently support dependencies from the module namespace
include dirname(__FILE__) . '/vendor/autoload.php';

use PrestaShop\Module\AdresValidatie\Service\AsyncService;
use PrestaShop\Module\AdresValidatie\Service\ConfigurationService;
use PrestaShop\Module\AdresValidatie\Service\DatabaseService;

class AdresValidatie extends Module
{
    /**
     * @var ConfigurationService $configurationService
     */
    private $configurationService;

    /**
     * @var AsyncService $asyncService
     */
    private $asyncService;

    /**
     * @var DatabaseService $databaseService
     */
    private $databaseService;

    public function __construct()
    {
        $this->name = 'adresvalidatie';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'adres-validatie.nl';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = 'Adres Validatie';
        $this->description = $this->l('Autocompletes the address forms for the Netherlands based on postcode and house number.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        // Prestashop does not consistently support auto-wiring
        $this->configurationService = new ConfigurationService();
        $this->asyncService = new AsyncService($this->configurationService);
        $this->databaseService = new DatabaseService();
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->get('router')->generate('adres_validatie_configuration'));
    }

    /**
     * @return bool
     */
    public function install()
    {
        if (!$this->databaseService->migrateUp()) {
            return false;
        }

        // TODO: register hooks?

        $this->asyncService->ajaxExecute('Import', 'startDemoImport');

        if (!parent::install()) {
            return false;
        }

        // Prestashop can not find the module routes unless cache is deleted
        array_map('unlink', glob(_PS_CACHE_DIR_ . '/*.*'));

        return true;
    }

    public function uninstall()
    {
        if(!parent::uninstall()) {
            return false;
        }

        if (!$this->databaseService->migrateDown()) {
            return false;
        }

        $this->configurationService->deleteAll();

        return true;
    }
}
