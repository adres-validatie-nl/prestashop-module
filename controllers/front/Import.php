<?php
// Prestashop does not support dependencies from the module namespace front-controller
include dirname(dirname(dirname(__FILE__))) . '/vendor/autoload.php';

use PrestaShop\Module\AdresValidatie\Service\AsyncService;
use PrestaShop\Module\AdresValidatie\Service\ConfigurationService;
use PrestaShop\Module\AdresValidatie\Service\DatabaseService;

class AdresValidatieImportModuleFrontController extends ModuleFrontController
{
    /**
     * @var bool $test_mode
     */
    private $test_mode = false;

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
        parent::__construct();

        // Prestashop does not support auto-wiring for front-controllers
        $this->configurationService = new ConfigurationService();
        $this->asyncService = new AsyncService($this->configurationService);
        $this->databaseService = new DatabaseService();

        if (getenv('ADRESVALIDATIE_IMPORT_TEST_MODE') === '1') {
            $this->test_mode = true;
        }
    }

    public function display()
    {
        if (Tools::getValue('nonce') !== $this->configurationService->get('ajax_nonce')) {
            error_log('invalid nonce received: "' . Tools::getValue('nonce') . '" expected: "' . $this->configurationService->get('ajax_nonce') . '"');
            die('Invalid nonce');
        }

        switch (Tools::getValue('action')) {
            case 'startDemoImport':
                $this->startDemoImport();
                break;
            case 'startImport':
                $this->startImport();
                break;
            case 'continueProcessing':
                $this->continueProcessing();
                break;
            default:
                die('Invalid action');
        }
    }

    public function startDemoImport()
    {
        $this->startProcessing(_PS_MODULE_DIR_ . 'adresvalidatie/data/demo_addresses.csv');
        $this->asyncService->ajaxExecute('Import', 'continueProcessing');
    }

    public function startImport()
    {
        $this->startProcessing(_PS_MODULE_DIR_ . 'adresvalidatie/data/addresses.csv');
        $this->asyncService->ajaxExecute('Import', 'continueProcessing');
    }

    public function continueProcessing()
    {
        $finished = $this->processCurrentCsv();
        if ($finished) {
            $this->configurationService->set('async_activity', null);
            $this->databaseService->replaceWithTemporaryTable();
            return;
        }
        $this->asyncService->ajaxExecute('Import', 'continueProcessing');
    }

    private function startProcessing(string $filename)
    {
        $this->configurationService->set('async_activity', 'process_csv');
        $this->configurationService->set('csv_filename', $filename);

        $this->databaseService->createTemporaryTable();

        // store the amount of lines in file
        $file = fopen($filename, 'r');
        if (!$file) {
            die('Error opening file.');
        }
        $count = 0;
        while (!feof($file)) {
            fgets($file);
            $count++;
        }
        fclose($file);

        $this->configurationService->set('csv_row_count', $count);
        $this->configurationService->set('csv_rows_processed', 0);
    }

    private function processCurrentCsv()
    {
        $filename = $this->configurationService->get('csv_filename');
        $rowsProcessed = $this->configurationService->get('csv_rows_processed');
        $skip = 1 + $rowsProcessed;

        $start_time = time();
        $max_time = ini_get('max_execution_time') - 5;
        if ($this->test_mode) {
            $max_time = 4;
        }

        if (!file_exists($filename)) {
            throw new \Exception("tried to import file that does not exist: '$filename'");
        }
        $handle = fopen($filename, 'r');
        if (!$handle) {
            throw new \Exception("could not open file: '$filename'");
        }

        fgetcsv($handle);

        $batch_size = 1000;
        if ($this->test_mode) {
            $batch_size = 10; // small batches for testing
        }
        $rows = [];
        $i = 0;
        $completed = true;
        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            $i++;
            if ($i < $skip) {
                continue;
            }

            $rows[] = $data;

            if (count($rows) == $batch_size) {
                if ($this->test_mode){
                    sleep(1); // Make importing deliberately slow for testing
                }

                $this->databaseService->insertIntoTemporaryTable($rows);
                $rowsProcessed += count($rows);
                $rows = [];

                $this->configurationService->set('csv_rows_processed', $rowsProcessed);

                if (time() - $start_time > $max_time) {
                    $completed = false;
                    break;
                }
            }
        }
        if (!empty($rows)) {
            $rowsProcessed += count($rows);
            $this->configurationService->set('csv_rows_processed', $rowsProcessed);
            $this->databaseService->insertIntoTemporaryTable($rows);
        }

        return $completed;
    }
}
