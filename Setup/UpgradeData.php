<?php

namespace Ess\M2ePro\Setup;

use Ess\M2ePro\Model\Config\Manager\Module;
use Ess\M2ePro\Model\Exception;
use Ess\M2ePro\Setup\Upgrade\Entity;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements UpgradeDataInterface
{
    /** @var \Magento\Framework\Module\ModuleResource $moduleResource */
    protected $moduleResource;

    /** @var \Magento\Framework\Module\ModuleListInterface $moduleList */
    protected $moduleList;

    /** @var Upgrade\EntityFactory $entityFactory */
    protected $entityFactory;

    /** @var Module $moduleConfig */
    protected $moduleConfig;

    /** @var \Ess\M2ePro\Helper\Factory $helperFactory */
    protected $helperFactory;

    /** @var  Tables $tablesObject */
    protected $tablesObject;

    /** @var  ModuleDataSetupInterface $installer */
    protected $installer;

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
    private static $availableVersionUpgrades = [];

    //########################################

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Ess\M2ePro\Setup\Upgrade\EntityFactory $entityFactory,
        Module $moduleConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        Tables $tablesObject
    ) {
        $this->moduleResource = new \Magento\Framework\Module\ModuleResource($context);
        $this->moduleList = $moduleList;
        $this->entityFactory = $entityFactory;
        $this->moduleConfig = $moduleConfig;
        $this->helperFactory = $helperFactory;
        $this->tablesObject = $tablesObject;
    }

    //########################################

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (!$this->isInstalled()) {
            return;
        }

        if ($this->helperFactory->getObject('Module\Maintenance\Setup')->isEnabled()) {
            return;
        }

        $this->installer = $setup;

        try {

            if (!$this->isNeedRollbackBackup() && !empty($this->getNotCompletedUpgrades())) {
                throw new Exception('There are some not completed previous upgrades');
            }

            $versionFrom = $this->prepareVersionFrom();
            $versionTo   = $this->prepareVersionTo();

            if ($versionFrom == $versionTo) {
                return;
            }

            $this->helperFactory->getObject('Module\Maintenance\Setup')->enable();

            while ($upgradeEntity = $this->getUpgradeEntity($versionFrom, $versionTo, !empty($versionTo))) {

                if ($this->isNeedRollbackBackup()) {
                    $upgradeEntity->getBackup()->rollback();
                    $this->unsetIsNeedRollbackBackup();
                }

                $upgradeEntity->process();

                $versionFrom = $upgradeEntity->getVersionTo();
                $versionTo   = null;

                $this->setResourceVersion($upgradeEntity->getVersionTo());
            }

            $this->helperFactory->getObject('Module\Maintenance\Setup')->disable();

            $this->setResourceVersion($this->getConfigVersion());
        } catch (\Exception $exception) {
            $this->helperFactory->getObject('Module\Exception')->process($exception);
            return;
        }
    }

    //########################################

    private function prepareVersionFrom()
    {
        $maxSetupVersion = $this->getMaxSetupToVersion();
        if (!is_null($maxSetupVersion) && $maxSetupVersion != $this->getResourceVersion()) {
            $this->setResourceVersion($maxSetupVersion);
        }

        $versionFrom = $this->getResourceVersion();

        if ($this->isNeedRollbackBackup()) {
            $versionFrom = $this->getVersionForRollbackBackup()['version_from'];
        }

        return $versionFrom;
    }

    private function prepareVersionTo()
    {
        $versionTo = null;

        if ($this->isNeedRollbackBackup()) {
            $versionTo = $this->getVersionForRollbackBackup()['version_to'];
        }

        return $versionTo;
    }

    //########################################

    private function getMaxSetupToVersion()
    {
        $select = $this->getConnection()->select()
            ->from($this->tablesObject->getFullName('setup'), 'version_to')
            ->where('is_completed = ?', 1);

        $toVersions = $this->getConnection()->fetchCol($select);

        $maxToVersion = null;

        foreach ($toVersions as $toVersion) {
            if (is_null($maxToVersion)) {
                $maxToVersion = $toVersion;
                continue;
            }

            if (version_compare($maxToVersion, $toVersion, '>')) {
                continue;
            }

            $maxToVersion = $toVersion;
        }

        return $maxToVersion;
    }

    private function getNotCompletedSetupVersions()
    {
        $select = $this->getConnection()->select()
            ->from($this->tablesObject->getFullName('setup'))
            ->where('is_backuped = ?', 1)
            ->where('is_completed = ?', 0);

        $setupRecords = $this->getConnection()->fetchAssoc($select);

        $resultVersions = [];

        foreach ($setupRecords as $setupRecord) {
            $resultVersions[] = [
                'version_from' => $setupRecord['version_from'],
                'version_to'   => $setupRecord['version_to'],
            ];
        }

        return $resultVersions;
    }

    private function getVersionForRollbackBackup()
    {
        $versions = $this->getNotCompletedSetupVersions();

        if (count($versions) != 1 || reset($versions)['version_from'] != $this->getResourceVersion()) {
            throw new Exception('Invalid preconditions for rollback backup');
        }

        return reset($versions);
    }

    private function getNotCompletedUpgrades()
    {
        $select = $this->getConnection()->select()
            ->from($this->tablesObject->getFullName('setup'))
            ->where('version_from IS NOT NULL')
            ->where('version_to IS NOT NULL')
            ->where('is_backuped = ?', 1)
            ->where('is_completed = ?', 0);

        return $this->getConnection()->fetchAssoc($select);
    }

    //########################################

    /**
     * @param $versionFrom
     * @param $versionTo
     * @param $strictMode
     * @return Entity
     */
    private function getUpgradeEntity($versionFrom, $versionTo = null, $strictMode = false)
    {
        if ($strictMode) {
            if (!isset(self::$availableVersionUpgrades[$versionFrom])) {
                return false;
            }

            if (!in_array($versionTo, self::$availableVersionUpgrades[$versionFrom])) {
                return false;
            }

            return $this->entityFactory->create([
                'versionFrom' => $versionFrom,
                'versionTo'   => $versionTo,
                'installer'   => $this->installer,
            ]);
        }

        $allFromVersions = array_keys(self::$availableVersionUpgrades);

        $versionsSortCallback = function($firstVersion, $secondVersion) {
            return version_compare($firstVersion, $secondVersion, '>') ? 1 : -1;
        };

        usort($allFromVersions, $versionsSortCallback);

        $calculatedVersionFrom = null;
        foreach ($allFromVersions as $tempFromVersion) {
            if (version_compare($versionFrom, $tempFromVersion, '>')) {
                continue;
            }

            $calculatedVersionFrom = $tempFromVersion;
            break;
        }

        if (is_null($calculatedVersionFrom)) {
            return false;
        }

        $toVersions = self::$availableVersionUpgrades[$calculatedVersionFrom];
        usort($toVersions, $versionsSortCallback);

        $calculatedVersionTo = end($toVersions);

        return $this->entityFactory->create([
            'versionFrom' => $calculatedVersionFrom,
            'versionTo'   => $calculatedVersionTo,
            'installer'   => $this->installer,
        ]);
    }

    //########################################

    private function isInstalled()
    {
        return !empty($this->moduleResource->getDbVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER)) &&
               !empty($this->moduleResource->getDataVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER));
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

    private function isNeedRollbackBackup()
    {
        return $this->moduleConfig->getGroupValue('/setup/upgrade/', 'is_need_rollback_backup');
    }

    private function unsetIsNeedRollbackBackup()
    {
        return $this->moduleConfig->setGroupValue('/setup/upgrade/', 'is_need_rollback_backup', 0);
    }

    //########################################

    /**
     * @return Mysql
     */
    private function getConnection()
    {
        return $this->installer->getConnection();
    }

    //########################################
}