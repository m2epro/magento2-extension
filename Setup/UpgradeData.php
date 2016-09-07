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
        '1.0.0' => ['1.1.0']
    ];

    //########################################

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        Module $moduleConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->moduleResource = new \Magento\Framework\Module\ModuleResource($context);
        $this->moduleList = $moduleList;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->moduleConfig = $moduleConfig;
        $this->helperFactory = $helperFactory;
        $this->modelFactory = $modelFactory;
    }

    //########################################

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->installer = $setup;

        if (!$this->isInstalled()) {
            return;
        }

        if ($this->helperFactory->getObject('Module\Maintenance\General')->isEnabled() &&
            !$this->isMaintenanceCanBeIgnored()
        ) {
            return;
        }

        try {

            $this->checkPreconditions();

            $versionsToExecute = $this->getVersionsToExecute();
            if (empty($versionsToExecute)) {
                return;
            }

            $this->helperFactory->getObject('Module\Maintenance\General')->enable();

            foreach ($versionsToExecute as $versionFrom => $versionTo) {

                /** @var Manager $upgradeManager */
                $upgradeManager = $this->modelFactory->getObject('Setup\Upgrade\Manager', [
                    'versionFrom' => $versionFrom,
                    'versionTo'   => $versionTo,
                    'installer'   => $this->installer,
                ]);

                $setupObject  = $this->initSetupObject($versionFrom, $versionTo);
                $backupObject = $upgradeManager->getBackupObject();

                if ($this->isAllowedRollbackFromBackup()) {
                    $backupObject->rollback();
                }

                $backupObject->remove();
                $backupObject->create();
                $setupObject->setData('is_backuped', 1)->save();

                $upgradeManager->process();

                $setupObject->setData('is_completed', 1)->save();
                $backupObject->remove();

                $this->setResourceVersion($versionTo);
            }

            $this->helperFactory->getObject('Module\Maintenance\General')->disable();

            $this->setResourceVersion($this->getConfigVersion());
        } catch (\Exception $exception) {
            $this->helperFactory->getObject('Module\Exception')->process($exception);
            return;
        }
    }

    //########################################

    private function isInstalled()
    {
        return !empty($this->moduleResource->getDbVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER)) &&
               !empty($this->moduleResource->getDataVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER));
    }

    private function checkPreconditions()
    {
        $maxSetupVersion = $this->activeRecordFactory->getObject('Setup')
            ->getResource()
            ->getMaxCompletedItem()
            ->getVersionTo();

        if (!is_null($maxSetupVersion) && $maxSetupVersion != $this->getResourceVersion()) {
            $this->setResourceVersion($maxSetupVersion);
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
                reset($notCompletedUpgrades)->getVersionFrom() != $this->getResourceVersion()
            ) {
                throw new Exception('Not completed upgrade is invalid for rollback from backup');
            }
        }
    }

    private function getVersionsToExecute()
    {
        $resultVersions = [];

        // we must execute last failed upgrade first
        if ($this->isAllowedRollbackFromBackup()) {
            /** @var Setup[] $notCompletedUpgrades */
            $notCompletedUpgrades = $this->getNotCompletedUpgrades();
            $notCompletedUpgrade  = reset($notCompletedUpgrades);

            $resultVersions[$notCompletedUpgrade->getVersionFrom()] = $notCompletedUpgrade->getVersionTo();
        }

        $versionFrom = end($resultVersions);
        if (empty($versionFrom)) {
            $versionFrom = $this->getResourceVersion();
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

    private function setResourceVersion($version)
    {
        $this->moduleResource->setDbVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER, $version);
        $this->moduleResource->setDataVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER, $version);
    }

    private function getResourceVersion()
    {
        return $this->moduleResource->getDataVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER);
    }

    //########################################
}