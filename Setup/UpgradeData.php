<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup;

use Ess\M2ePro\Model\Exception;
use Ess\M2ePro\Model\Setup;
use Ess\M2ePro\Model\Setup\Upgrade\Manager;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class \Ess\M2ePro\Setup\UpgradeData
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * Means that version, upgrade files are included to the build
     */
    const MIN_SUPPORTED_VERSION_FOR_UPGRADE = '1.0.0';

    /** @var \Magento\Framework\Module\ModuleResource $moduleResource */
    private $moduleResource;

    /** @var \Magento\Framework\Module\ModuleListInterface $moduleList */
    private $moduleList;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory */
    private $activeRecordFactory;

    /** @var \Ess\M2ePro\Helper\Factory $helperFactory */
    private $helperFactory;

    /** @var \Ess\M2ePro\Model\Factory $modelFactory */
    private $modelFactory;

    /** @var ModuleDataSetupInterface $installer */
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
        '1.3.3' => ['1.3.4'],
        '1.3.4' => ['1.4.0'],
        '1.4.0' => ['1.4.1'],
        '1.4.1' => ['1.4.2'],
        '1.4.2' => ['1.4.3'],
        '1.4.3' => ['1.5.0'],
        '1.5.0' => ['1.5.1'],
        '1.5.1' => ['1.6.0'],
        '1.6.0' => ['1.7.0'],
        '1.7.0' => ['1.7.1'],
        '1.7.1' => ['1.7.2'],
        '1.7.2' => ['1.8.0'],
        '1.8.0' => ['1.8.1'],
        '1.8.1' => ['1.9.0'],
        '1.9.0' => ['1.9.1'],
        '1.9.1' => ['1.9.2'],
        '1.9.2' => ['1.9.3'],
        '1.9.3' => ['1.9.4'],
        '1.9.4' => ['1.9.5'],
        '1.9.5' => ['1.10.0'],
        '1.10.0' => ['1.10.1'],
        '1.10.1' => ['1.11.0'],
        '1.11.0' => ['1.12.0'],
        '1.12.0' => ['1.12.1'],
        '1.12.1' => ['1.12.2'],
        '1.12.2' => ['1.12.3'],
        '1.12.3' => ['1.13.0'],
        '1.13.0' => ['1.13.1'],
        '1.13.1' => ['1.13.2'],
        '1.13.2' => ['1.14.0'],
        '1.14.0' => ['1.14.1'],
        '1.14.1' => ['1.14.2']
    ];

    //########################################

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Setup\LoggerFactory $loggerFactory
    ) {
        $this->moduleResource = new \Magento\Framework\Module\ModuleResource($context);
        $this->moduleList = $moduleList;
        $this->activeRecordFactory = $activeRecordFactory;
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

        if (!$this->isInstalled()) {
            return;
        }

        $this->installer->startSetup();
        $this->helperFactory->getObject('Module\Maintenance')->enable();

        try {
            $versionsToExecute = $this->getVersionsToExecute();
            foreach ($versionsToExecute as $versionFrom => $versionTo) {

                /** @var Manager $upgradeManager */
                $upgradeManager = $this->modelFactory->getObject('Setup_Upgrade_Manager', [
                    'versionFrom' => $versionFrom,
                    'versionTo'   => $versionTo,
                    'installer'   => $this->installer,
                ]);

                $setupObject  = $upgradeManager->getCurrentSetupObject();
                $backupObject = $upgradeManager->getBackupObject();

                if (!$setupObject->isBackuped()) {
                    $backupObject->create();
                    $setupObject->setData('is_backuped', 1);
                    $setupObject->save();
                }

                $upgradeManager->process();

                $setupObject->setData('is_completed', 1)->save();
                $backupObject->remove();

                $this->setMagentoResourceVersion($versionTo);
            }

            $this->setMagentoResourceVersion($this->getConfigVersion());
        } catch (\Exception $exception) {
            $this->logger->error($exception, ['source' => 'UpgradeData']);
            $this->helperFactory->getObject('Data\GlobalData')->setValue('is_setup_failed', true);

            if (isset($setupObject)) {
                $setupObject->setData('profiler_data', $exception->__toString());
                $setupObject->save();
            }

            $this->installer->endSetup();
            return;
        }

        $this->helperFactory->getObject('Module\Maintenance')->disable();
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

    private function getVersionsToExecute()
    {
        $versionFrom = $this->getMagentoResourceVersion();

        /** @var Setup[] $notCompletedUpgrades */
        $notCompletedUpgrades = $this->activeRecordFactory->getObject('Setup')->getResource()
            ->getNotCompletedUpgrades();

        if (!empty($notCompletedUpgrades)) {
            /**
             * Only one not completed upgrade is supported
             */
            $notCompletedUpgrade = reset($notCompletedUpgrades);
            if (version_compare($notCompletedUpgrade->getVersionFrom(), $versionFrom, '<')) {
                $versionFrom = $notCompletedUpgrade->getVersionFrom();
            }
        }

        if (version_compare($versionFrom, self::MIN_SUPPORTED_VERSION_FOR_UPGRADE, '<')) {
            // @codingStandardsIgnoreLine
            throw new Exception(sprintf('This version [%s] is too old.', $versionFrom));
        }

        $versions = [];
        while ($versionFrom != $this->getConfigVersion()) {
            $versionTo = !empty(self::$availableVersionUpgrades[$versionFrom])
                ? end(self::$availableVersionUpgrades[$versionFrom])
                : null;

            if ($versionTo === null) {
                break;
            }

            $versions[$versionFrom] = $versionTo;
            $versionFrom = $versionTo;
        }

        return $versions;
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
        return $this->helperFactory->getObject('Module_Database_Tables')->getFullName($tableName);
    }

    //########################################
}
