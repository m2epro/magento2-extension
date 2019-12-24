<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup;

use Ess\M2ePro\Model\VariablesDir;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class \Ess\M2ePro\Setup\Uninstall
 */
class Uninstall implements \Magento\Framework\Setup\UninstallInterface
{
    private $variablesDir = null;
    private $deploymentConfig = null;

    /** @var  ModuleDataSetupInterface $installer */
    private $installer;

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
        $this->installer = $setup;

        try {
            if (!$this->canRemoveData()) {
                return;
            }

            // Filesystem
            // -----------------------
            $this->variablesDir->removeBase();
            // -----------------------

            // Database
            // -----------------------
            $tables = $setup->getConnection()->getTables(
                (string)$this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX).'m2epro_%'
            );

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

    private function canRemoveData()
    {
        $select = $this->installer->getConnection()
            ->select()
            ->from($this->installer->getTable('m2epro_module_config'), 'value')
            ->where('`group` = ?', '/uninstall/')
            ->where('`key` = ?', 'can_remove_data');

        return (bool)$this->installer->getConnection()->fetchOne($select);
    }

    //########################################
}
