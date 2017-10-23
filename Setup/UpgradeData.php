<?php

namespace Ess\M2ePro\Setup;

use Ess\M2ePro\Model\Config\Manager\Module;
use Ess\M2ePro\Model\Exception;
use Ess\M2ePro\Model\Setup;
use Ess\M2ePro\Model\Setup\Upgrade\Manager;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements UpgradeDataInterface
{
    /** @var \Magento\Framework\Module\ModuleResource $moduleResource */
    private $moduleResource;

    /** @var \Magento\Framework\Module\ModuleListInterface $moduleList */
    private $moduleList;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory */
    private $activeRecordFactory;

    /** @var Module $moduleConfig */
    private $moduleConfig;

    /** @var \Ess\M2ePro\Helper\Factory $helperFactory */
    private $helperFactory;

    /** @var \Ess\M2ePro\Model\Factory $modelFactory */
    private $modelFactory;

    /** @var  ModuleDataSetupInterface $installer */
    private $installer;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * @format
     * [
     *     'from_version1' => [
     *         'to_version1',
     *         'to_version2',
     *         ...
     *     ],
     *     ...
     * ]
     *
     * @var array
     */
    private static $availableVersionUpgrades = [
        '1.0.0' => ['1.1.0'],
        '1.1.0' => ['1.2.0'],
        '1.2.0' => ['1.3.0'],
        '1.3.0' => ['1.3.1'],
        '1.3.1' => ['1.3.2'],
        '1.3.2' => ['1.3.3'],
    ];

    //########################################

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        Module $moduleConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Setup\LoggerFactory $loggerFactory
    ) {
        $this->moduleResource = new \Magento\Framework\Module\ModuleResource($context);
        $this->moduleList = $moduleList;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->moduleConfig = $moduleConfig;
        $this->helperFactory = $helperFactory;
        $this->modelFactory = $modelFactory;

        $this->logger = $loggerFactory->create();
    }

    //########################################

    /**
     * Module versions from setup_module magento table uses only by magento for run install or upgrade files.
     * We do not use these versions in setup & upgrade logic (only set correct values to it, using m2epro_setup table).
     * So version, that presented in $context parameter, is not used.
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->installer = $setup;

        if ($this->helperFactory->getObject('Data\GlobalData')->getValue('is_setup_failed')) {
            return;
        }

        if ($this->helperFactory->getObject('Data\GlobalData')->getValue('is_install_process')) {
            return;
        }

        if ($this->helperFactory->getObject('Module\Maintenance\General')->isEnabled() &&
            !$this->isMaintenanceCanBeIgnored()) {
            return;
        }

        if (!$this->isInstalled()) {
            return;
        }

        $this->checkPreconditions();

        $this->installer->startSetup();
        $this->helperFactory->getObject('Module\Maintenance\General')->enable();

        try {

            $versionsToExecute = $this->getVersionsToExecute();
            foreach ($versionsToExecute as $versionFrom => $versionTo) {

                /** @var Manager $upgradeManager */
                $upgradeManager = $this->modelFactory->getObject('Setup\Upgrade\Manager', [
                    'versionFrom' => $versionFrom,
                    'versionTo'   => $versionTo,
                    'installer'   => $this->installer,
                ]);

                $setupObject  = $this->initSetupObject($versionFrom, $versionTo);
                $backupObject = $upgradeManager->getBackupObject();

                if ($setupObject->isBackuped() && $this->isAllowedRollbackFromBackup()) {
                    $backupObject->rollback();
                }

                $backupObject->remove();
                $backupObject->create();
                $setupObject->setData('is_backuped', 1)->save();

                $upgradeManager->process();

                $setupObject->setData('is_completed', 1)->save();
                $backupObject->remove();

                $this->setMagentoResourceVersion($versionTo);
            }

        } catch (\Exception $exception) {

            $this->logger->error($exception, ['source' => 'UpgradeData']);
            $this->helperFactory->getObject('Module\Exception')->process($exception);
            $this->helperFactory->getObject('Data\GlobalData')->setValue('is_setup_failed', true);

            $this->installer->endSetup();
            return;
        }

        $this->helperFactory->getObject('Module\Maintenance\General')->disable();
        $this->installer->endSetup();
    }

    //########################################

    private function isInstalled()
    {
        if (!$this->getConnection()->isTableExists($this->getFullTableName('setup'))) {
            return false;
        }

        $setupRow = $this->getConnection()->select()
            ->from($this->getFullTableName('setup'))
            ->where('version_from IS NULL')
            ->where('is_completed = ?', 1)
            ->query()
            ->fetch();

        return $setupRow !== false;
    }

    private function checkPreconditions()
    {
        $maxSetupVersion = $this->activeRecordFactory->getObject('Setup')
            ->getResource()
            ->getMaxCompletedItem();

        $maxSetupVersion && $maxSetupVersion = $maxSetupVersion->getVersionTo();

        if (!is_null($maxSetupVersion) && $maxSetupVersion != $this->getMagentoResourceVersion()) {
            $this->setMagentoResourceVersion($maxSetupVersion);
        }

        $notCompletedUpgrades = $this->getNotCompletedUpgrades();

        if (!empty($notCompletedUpgrades) && !$this->isAllowedRollbackFromBackup()) {
            throw new Exception('There are some not completed previous upgrades');
        }

        if ($this->isAllowedRollbackFromBackup()) {

            // only 1 not completed upgrade allowed for rollback from backup

            if (count($notCompletedUpgrades) > 1) {
                throw new Exception('There are more than 1 not completed previous upgrades available');
            }

            if (!empty($notCompletedUpgrades) &&
                reset($notCompletedUpgrades)->getVersionFrom() != $this->getMagentoResourceVersion()
            ) {
                throw new Exception('Not completed upgrade is invalid for rollback from backup');
            }
        }
    }

    private function getVersionsToExecute()
    {
        $resultVersions = [];

        // we must execute last failed upgrade first
        if ($this->isAllowedRollbackFromBackup() && ($notCompletedUpgrades = $this->getNotCompletedUpgrades())) {
            /** @var Setup[] $notCompletedUpgrades */

            $notCompletedUpgrade = reset($notCompletedUpgrades);
            $resultVersions[$notCompletedUpgrade->getVersionFrom()] = $notCompletedUpgrade->getVersionTo();
        }

        $versionFrom = end($resultVersions);
        if (empty($versionFrom)) {
            $versionFrom = $this->getMagentoResourceVersion();
        }

        while ($versionFrom != $this->getConfigVersion()) {
            $versionTo = end(self::$availableVersionUpgrades[$versionFrom]);
            $resultVersions[$versionFrom] = $versionTo;

            $versionFrom = $versionTo;
        }

        return $resultVersions;
    }

    //########################################

    /**
     * @return Setup[]
     */
    private function getNotCompletedUpgrades()
    {
        $collection = $this->activeRecordFactory->getObject('Setup')->getCollection();
        $collection->addFieldToFilter('version_from', array('notnull' => true));
        $collection->addFieldToFilter('version_to', array('notnull' => true));
        $collection->addFieldToFilter('is_backuped', 1);
        $collection->addFieldToFilter('is_completed', 0);

        return $collection->getItems();
    }

    private function initSetupObject($versionFrom, $versionTo)
    {
        $collection = $this->activeRecordFactory->getObject('Setup')->getCollection();
        $collection->addFieldToFilter('version_from', $versionFrom);
        $collection->addFieldToFilter('version_to', $versionTo);
        $collection->getSelect()->limit(1);

        /** @var Setup $setupObject */
        $setupObject = $collection->getFirstItem();

        if (!$setupObject->getId()) {
            $setupObject->setData([
                'version_from' => $versionFrom,
                'version_to'   => $versionTo,
                'is_backuped'  => 0,
                'is_completed' => 0,
            ]);
            $setupObject->save();
        }

        return $setupObject;
    }

    private function isMaintenanceCanBeIgnored()
    {
        $select = $this->installer->getConnection()
            ->select()
            ->from($this->installer->getTable('core_config_data'), 'value')
            ->where('scope = ?', 'default')
            ->where('scope_id = ?', 0)
            ->where('path = ?', 'm2epro/setup/ignore_maintenace');

        return (bool)$this->installer->getConnection()->fetchOne($select);
    }

    private function isAllowedRollbackFromBackup()
    {
        $select = $this->installer->getConnection()
            ->select()
            ->from($this->installer->getTable('core_config_data'), 'value')
            ->where('scope = ?', 'default')
            ->where('scope_id = ?', 0)
            ->where('path = ?', 'm2epro/setup/allow_rollback_from_backup');

        return (bool)$this->installer->getConnection()->fetchOne($select);
    }

    //########################################

    private function getConfigVersion()
    {
        return $this->moduleList->getOne(\Ess\M2ePro\Helper\Module::IDENTIFIER)['setup_version'];
    }

    private function setMagentoResourceVersion($version)
    {
        $this->moduleResource->setDbVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER, $version);
        $this->moduleResource->setDataVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER, $version);
    }

    private function getMagentoResourceVersion()
    {
        return $this->moduleResource->getDataVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER);
    }

    //########################################

    /**
     * @return \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    private function getConnection()
    {
        return $this->installer->getConnection();
    }

    private function getFullTableName($tableName)
    {
        return $this->helperFactory->getObject('Module\Database\Tables')->getFullName($tableName);
    }

    //########################################
}