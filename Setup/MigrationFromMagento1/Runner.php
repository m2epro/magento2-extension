<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\MigrationFromMagento1;

use Ess\M2ePro\Helper\Factory as HelperFactory;
use Ess\M2ePro\Model\Wizard\MigrationFromMagento1;
use Magento\Setup\Module\Setup as MagentoSetup;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;

/**
 * Class \Ess\M2ePro\Setup\MigrationFromMagento1\Runner
 */
class Runner
{
    /** @var Mapper */
    private $mapper;

    /** @var HelperFactory */
    private $helperFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;

    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    /** @var MagentoSetup */
    private $installer;

    /** @var DeploymentConfig */
    private $deploymentConfig;

    /** @var PreconditionsChecker\AbstractModel */
    private $preconditionsChecker;

    /** @var Modifier */
    private $dbModifier;

    //########################################

    /**
     * Runner constructor.
     * @param Mapper $mapper
     * @param HelperFactory $helperFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        Mapper $mapper,
        HelperFactory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        DeploymentConfig $deploymentConfig,
        Modifier $dbModifier
    ) {
        $this->mapper              = $mapper;
        $this->helperFactory       = $helperFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->objectManager       = $objectManager;
        $this->resourceConnection  = $resourceConnection;
        $this->installer           = new MagentoSetup($resourceConnection);
        $this->deploymentConfig    = $deploymentConfig;
        $this->dbModifier          = $dbModifier;
    }

    //########################################

    /**
     * @param PreconditionsChecker\AbstractModel $preconditionsChecker
     * @return $this
     */
    public function setPreconditionsChecker(PreconditionsChecker\AbstractModel $preconditionsChecker)
    {
        $this->preconditionsChecker = $preconditionsChecker;
        return $this;
    }

    //########################################

    public function prepare()
    {
        $structureHelper = $this->helperFactory->getObject('Module_Database_Structure');
        $this->helperFactory->getObject('Module\Maintenance')->enable();

        $allTables  = $structureHelper->getModuleTables();
        $skipTables = [
            'm2epro_setup',
            'm2epro_config',
        ];

        foreach ($allTables as $tableName) {
            if (in_array($tableName, $skipTables)) {
                continue;
            }

            $this->resourceConnection->getConnection()->dropTable(
                $structureHelper->getTableNameWithPrefix($tableName)
            );
        }

        $this->resourceConnection->getConnection()->renameTable(
            $structureHelper->getTableNameWithPrefix('m2epro_config'),
            $structureHelper->getTableNameWithPrefix('m2epro_config_m2_original')
        );

        $this->helperFactory->getObject('Magento')->clearCache();
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function run()
    {
        $this->getPreconditionsChecker()->checkPreconditions();
        $this->prepareTablesPrefixes();

        $this->dbModifier->process();

        $this->mapper->map();

        $this->createSetupRecord();
        $this->cleanUp();

        $this->helperFactory->getObject('Magento')->clearCache();
    }

    /**
     * @return PreconditionsChecker\AbstractModel
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getPreconditionsChecker()
    {
        if ($this->preconditionsChecker === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Preconditions checker was not set.');
        }

        return $this->preconditionsChecker;
    }

    protected function cleanUp()
    {
        $structureHelper = $this->helperFactory->getObject('Module_Database_Structure');
        $this->resourceConnection->getConnection()->dropTable(
            $structureHelper->getTableNameWithPrefix('m2epro_config_m2_original')
        );
    }

    //----------------------------------------

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function prepareTablesPrefixes()
    {
        /** @var \Ess\M2ePro\Model\Wizard\MigrationFromMagento1 $wizard */
        $wizard = $this->helperFactory->getObject('Module_Wizard')->getWizard(MigrationFromMagento1::NICK);
        $oldTablesPrefix = $wizard->getM1TablesPrefix();
        $currentTablesPrefix = (string)$this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX);

        if (trim($oldTablesPrefix) == trim($currentTablesPrefix)) {
            return;
        }

        foreach ($this->installer->getConnection()->getTables($oldTablesPrefix.'m2epro_%') as $oldTableName) {
            $clearTableName = str_replace($oldTablesPrefix.'m2epro_', '', $oldTableName);
            $this->installer->getConnection()->renameTable(
                $oldTableName,
                $this->helperFactory->getObject('Module_Database_Tables')->getFullName($clearTableName)
            );
        }
    }

    //########################################

     /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function createSetupRecord()
    {
        /** @var \Ess\M2ePro\Model\Setup $setupObject */
        $setupObject = $this->activeRecordFactory->getObject('Setup')->getResource()->getMaxCompletedItem();
        $magentoDataVersion = $this->helperFactory->getObject('Module')->getDataVersion();

        if ($setupObject !== null && $setupObject->getVersionTo() === $magentoDataVersion) {
            return;
        }

        $setupTable = $this->helperFactory->getObject('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_setup');

        $this->resourceConnection->getConnection()->truncateTable($setupTable);
        $this->resourceConnection->getConnection()
            ->insert(
                $setupTable,
                [
                    'version_from' => null,
                    'version_to'   => $magentoDataVersion,
                    'is_backuped'  => 0,
                    'is_completed' => 1,
                ]
            );
    }

    //########################################
}
