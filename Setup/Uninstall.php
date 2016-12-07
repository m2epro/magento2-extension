<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup;

use Ess\M2ePro\Model\VariablesDir;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class Uninstall implements \Magento\Framework\Setup\UninstallInterface
{
    private $variablesDir = NULL;
    private $deploymentConfig = NULL;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    //########################################

    public function __construct(
        VariablesDir $variablesDir,
        DeploymentConfig $deploymentConfig,
        \Ess\M2ePro\Setup\LoggerFactory $loggerFactory
    ) {
        $this->variablesDir     = $variablesDir;
        $this->deploymentConfig = $deploymentConfig;

        $this->logger = $loggerFactory->create();
    }

    //########################################

    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        try {

            $tablesPrefix = (string)$this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX);

            $canRemoveData = (bool)$setup->getConnection()->select()
                ->from($tablesPrefix.'m2epro_module_config')
                ->reset(\Zend_Db_Select::COLUMNS)
                ->columns('value')
                ->where('`group` = ?', '/uninstall/')
                ->where('`key` = ?', 'can_remove_data')
                ->query()->fetchColumn();

            if (!$canRemoveData) {
                return;
            }

            // Filesystem
            // -----------------------
            $this->variablesDir->removeBase();
            // -----------------------

            // Database
            // -----------------------
            $tables = $setup->getConnection()->getTables($tablesPrefix.'m2epro_%');

            foreach ($tables as $table) {
                $setup->getConnection()->dropTable($table);
            }

            $setup->getConnection()->delete(
                $setup->getTable('core_config_data'),
                ['path LIKE ?' => 'm2epro/%']
            );
            // -----------------------

        } catch (\Exception $exception) {
            $this->logger->error($exception, ['source' => 'Uninstall']);
        }
    }

    //########################################
}