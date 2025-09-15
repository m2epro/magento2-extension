<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Setup;

use Ess\M2ePro\Model\Exception;
use Magento\Framework\Setup\SetupInterface;

/**
 * Upgrader M2E extension
 */
class Upgrader
{
    /**
     * Means that version, upgrade files are included to the build
     */
    public const MIN_SUPPORTED_VERSION_FOR_UPGRADE = '1.0.0';

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var \Ess\M2ePro\Helper\Module\Maintenance */
    private $maintenance;

    /** @var \Ess\M2ePro\Model\ResourceModel\Setup */
    private $setupResource;

    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    /** @var \Magento\Framework\Module\ModuleListInterface */
    private $moduleList;

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
        '1.14.1' => ['1.14.2'],
        '1.14.2' => ['1.14.3'],
        '1.14.3' => ['1.14.3.1'],
        '1.14.3.1' => ['1.15.0'],
        '1.15.0' => ['1.15.1'],
        '1.15.1' => ['1.16.0'],
        '1.16.0' => ['1.16.1'],
        '1.16.1' => ['1.17.0'],
        '1.17.0' => ['1.17.1'],
        '1.17.1' => ['1.18.0'],
        '1.18.0' => ['1.18.1'],
        '1.18.1' => ['1.19.0'],
        '1.19.0' => ['1.19.1'],
        '1.19.1' => ['1.19.2'],
        '1.19.2' => ['1.20.0'],
        '1.20.0' => ['1.20.1'],
        '1.20.1' => ['1.20.2'],
        '1.20.2' => ['1.21.0'],
        '1.21.0' => ['1.21.1'],
        '1.21.1' => ['1.21.2'],
        '1.21.2' => ['1.21.3'],
        '1.21.3' => ['1.22.0.1'],
        '1.22.0.1' => ['1.22.1'],
        '1.22.1' => ['1.23.0'],
        '1.23.0' => ['1.23.1'],
        '1.23.1' => ['1.23.2'],
        '1.23.2' => ['1.24.0'],
        '1.24.0' => ['1.24.1'],
        '1.24.1' => ['1.25.0'],
        '1.25.0' => ['1.25.1'],
        '1.25.1' => ['1.26.0'],
        '1.26.0' => ['1.27.0'],
        '1.27.0' => ['1.28.0'],
        '1.28.0' => ['1.28.1'],
        '1.28.1' => ['1.28.2'],
        '1.28.2' => ['1.29.0'],
        '1.29.0' => ['1.30.0'],
        '1.30.0' => ['1.31.0'],
        '1.31.0' => ['1.31.1'],
        '1.31.1' => ['1.31.2'],
        '1.31.2' => ['1.32.0'],
        '1.32.0' => ['1.32.1'],
        '1.32.1' => ['1.33.0'],
        '1.33.0' => ['1.33.1'],
        '1.33.1' => ['1.34.0'],
        '1.34.0' => ['1.35.0'],
        '1.35.0' => ['1.35.1'],
        '1.35.1' => ['1.36.0'],
        '1.36.0' => ['1.37.0'],
        '1.37.0' => ['1.38.0'],
        '1.38.0' => ['1.38.1'],
        '1.38.1' => ['1.39.0'],
        '1.39.0' => ['1.40.0'],
        '1.40.0' => ['1.40.1'],
        '1.40.1' => ['1.40.2'],
        '1.40.2' => ['1.40.3'],
        '1.40.3' => ['1.41.0'],
        '1.41.0' => ['1.42.0'],
        '1.42.0' => ['1.43.0'],
        '1.43.0' => ['1.43.1'],
        '1.43.1' => ['1.43.2'],
        '1.43.2' => ['1.43.3'],
        '1.43.3' => ['1.43.4'],
        '1.43.4' => ['1.43.5'],
        '1.43.5' => ['1.44.0'],
        '1.44.0' => ['1.44.1'],
        '1.44.1' => ['1.44.2'],
        '1.44.2' => ['1.45.0'],
        '1.45.0' => ['1.45.1'],
        '1.45.1' => ['1.46.0'],
        '1.46.0' => ['1.47.0'],
        '1.47.0' => ['1.47.1'],
        '1.47.1' => ['1.48.0'],
        '1.48.0' => ['1.48.1'],
        '1.48.1' => ['1.49.0'],
        '1.49.0' => ['1.49.1'],
        '1.49.1' => ['1.49.2'],
        '1.49.2' => ['1.50.0'],
        '1.50.0' => ['1.50.1'],
        '1.50.1' => ['1.50.2'],
        '1.50.2' => ['1.50.3'],
        '1.50.3' => ['1.51.0'],
        '1.51.0' => ['1.51.1'],
        '1.51.1' => ['1.52.0'],
        '1.52.0' => ['1.53.0'],
        '1.53.0' => ['1.54.0'],
        '1.54.0' => ['1.54.1'],
        '1.54.1' => ['1.54.2'],
        '1.54.2' => ['1.55.0'],
        '1.55.0' => ['1.55.1'],
        '1.55.1' => ['1.56.0'],
        '1.56.0' => ['1.56.1'],
        '1.56.1' => ['1.57.0'],
        '1.57.0' => ['1.58.0'],
        '1.58.0' => ['1.58.1'],
        '1.58.1' => ['1.58.2'],
        '1.58.2' => ['1.58.4'],
        '1.58.4' => ['1.58.5'],
        '1.58.5' => ['1.58.6'],
        '1.58.6' => ['1.58.7'],
        '1.58.7' => ['1.58.8'],
        '1.58.8' => ['1.59.0'],
        '1.59.0' => ['1.59.1'],
        '1.59.1' => ['1.59.2'],
        '1.59.2' => ['1.59.3'],
        '1.59.3' => ['1.59.4'],
        '1.59.4' => ['1.59.5'],
        '1.59.5' => ['1.59.6'],
        '1.59.6' => ['1.59.7'],
        '1.59.7' => ['1.60.0'],
        '1.60.0' => ['1.61.0'],
        '1.61.0' => ['1.62.0'],
        '1.62.0' => ['1.62.1'],
        '1.62.1' => ['1.62.2'],
        '1.62.2' => ['1.63.0'],
        '1.63.0' => ['1.64.0'],
        '1.64.0' => ['1.64.1'],
        '1.64.1' => ['1.65.0'],
        '1.65.0' => ['1.65.1'],
        '1.65.1' => ['1.65.2'],
        '1.65.2' => ['1.66.0'],
        '1.66.0' => ['1.66.1'],
        '1.66.1' => ['1.66.2'],
        '1.66.2' => ['1.67.0'],
        '1.67.0' => ['1.67.1'],
        '1.67.1' => ['1.68.0'],
        '1.68.0' => ['1.68.1'],
        '1.68.1' => ['1.68.2'],
        '1.68.2' => ['1.69.0'],
        '1.69.0' => ['1.70.0'],
        '1.70.0' => ['1.70.1'],
        '1.70.1' => ['1.70.2'],
        '1.70.2' => ['1.71.0'],
        '1.71.0' => ['1.72.0'],
        '1.72.0' => ['1.72.1'],
        '1.72.1' => ['1.72.2'],
        '1.72.2' => ['1.73.0'],
        '1.73.0' => ['1.74.0'],
        '1.74.0' => ['1.74.1'],
        '1.74.1' => ['1.74.2'],
        '1.74.2' => ['1.74.3'],
        '1.74.3' => ['1.75.0'],
        '1.75.0' => ['1.75.1'],
        '1.75.1' => ['1.75.2'],
        '1.75.2' => ['1.75.3'],
        '1.75.3' => ['1.76.0'],
        '1.76.0' => ['1.76.1'],
        '1.76.1' => ['1.76.2'],
        '1.76.2' => ['1.77.0'],
        '1.77.0' => ['1.77.1'],
        '1.77.1' => ['1.77.2'],
        '1.77.2' => ['1.77.3'],
        '1.77.3' => ['1.77.4'],
        '1.77.4' => ['1.78.0'],
        '1.78.0' => ['1.79.0'],
        '1.79.0' => ['1.79.1'],
        '1.79.1' => ['1.79.2'],
        '1.79.2' => ['1.79.3'],
        '1.79.3' => ['1.79.4'],
        '1.79.4' => ['1.79.5'],
        '1.79.5' => ['1.80.0'],
        '1.80.0' => ['1.80.1'],
        '1.80.1' => ['1.80.2'],
        '1.80.2' => ['1.81.0'],
        '1.81.0' => ['1.82.0'],
        '1.82.0' => ['1.83.0'],
        '1.83.0' => ['1.83.1'],
        '1.83.1' => ['1.83.2'],
        '1.83.2' => ['1.83.3'],
        '1.83.3' => ['1.83.4'],
        '1.83.4' => ['1.84.0'],
        '1.84.0' => ['1.85.0'],
    ];

    //########################################

    /**
     * @param \Ess\M2ePro\Helper\Module\Maintenance $maintenance
     * @param \Ess\M2ePro\Setup\LoggerFactory $loggerFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Setup $setupResource
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Helper\Module\Maintenance $maintenance,
        \Ess\M2ePro\Setup\LoggerFactory $loggerFactory,
        \Ess\M2ePro\Model\ResourceModel\Setup $setupResource,
        \Magento\Framework\Module\ModuleListInterface $moduleList
    ) {
        $this->maintenance = $maintenance;
        $this->objectManager = $objectManager;
        $this->setupResource = $setupResource;
        $this->moduleList = $moduleList;

        $this->logger = $loggerFactory->create();
    }

    //########################################

    /**
     * Module versions from setup_module magento table uses only by magento for run install or upgrade files.
     * We do not use these versions in setup & upgrade logic (only set correct values to it, using m2epro_setup table).
     * So version, that presented in $context parameter, is not used.
     *
     * @param SetupInterface $setup
     */
    public function upgrade(SetupInterface $setup)
    {
        $setup->startSetup();
        $this->maintenance->enable();

        try {
            $versionsToExecute = $this->getVersionsToExecute();
            foreach ($versionsToExecute as $versionFrom => $versionTo) {
                $upgradeManager = $this->objectManager->create(
                    \Ess\M2ePro\Model\Setup\Upgrade\Manager::class,
                    [
                        'versionFrom' => $versionFrom,
                        'versionTo' => $versionTo,
                        'installer' => $setup,
                    ]
                );

                $setupObject = $upgradeManager->getCurrentSetupObject();
                $backupObject = $upgradeManager->getBackupObject();

                if (!$setupObject->isBackuped()) {
                    $backupObject->create();
                    $setupObject->setData('is_backuped', 1);
                    $setupObject->save();
                }

                $upgradeManager->process();

                $setupObject->setData('is_completed', 1);
                $setupObject->save();
                $backupObject->remove();
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception, ['source' => 'Upgrade']);

            if (isset($setupObject)) {
                $setupObject->setData('profiler_data', $exception->__toString());
                $setupObject->save();
            }

            $setup->endSetup();

            return;
        }

        $this->maintenance->disable();
        $setup->endSetup();
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getVersionsToExecute(): array
    {
        $versionFrom = $this->getLastInstalledVersion();

        $notCompletedUpgrades = $this->setupResource->getNotCompletedUpgrades();

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
        while ($versionFrom !== $this->getConfigVersion()) {
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

    /**
     * @return string
     */
    private function getConfigVersion(): string
    {
        return $this->moduleList->getOne(\Ess\M2ePro\Helper\Module::IDENTIFIER)['setup_version'];
    }

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getLastInstalledVersion(): string
    {
        $maxCompletedItem = $this->setupResource->getMaxCompletedItem();
        if ($maxCompletedItem->getId() === null) {
            return self::MIN_SUPPORTED_VERSION_FOR_UPGRADE;
        }

        return $maxCompletedItem->getVersionTo();
    }
}
