<?php

namespace Ess\M2ePro\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Entry point for installation or upgrade M2E extension
 */
class RecurringData implements InstallDataInterface
{
    private const MINIMUM_REQUIRED_MAGENTO_VERSION = '2.4.0';

    /** @var \Ess\M2ePro\Model\Setup\InstallChecker */
    private $installChecker;
    /** @var \Ess\M2ePro\Model\Setup\Installer */
    private $installer;
    /** @var \Ess\M2ePro\Model\Setup\Upgrader */
    private $upgrader;
    /** @var \Magento\Framework\App\ProductMetadataInterface */
    private $productMetadata;
    /** @var \Ess\M2ePro\Helper\Module\Maintenance */
    private $maintenanceHelper;

    public function __construct(
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Ess\M2ePro\Helper\Module\Maintenance $maintenanceHelper,
        \Ess\M2ePro\Model\Setup\InstallChecker $installChecker,
        \Ess\M2ePro\Model\Setup\Installer $installer,
        \Ess\M2ePro\Model\Setup\Upgrader $upgrader
    ) {
        $this->installChecker = $installChecker;
        $this->installer = $installer;
        $this->upgrader = $upgrader;
        $this->productMetadata = $productMetadata;
        $this->maintenanceHelper = $maintenanceHelper;
    }

    /**
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface   $context
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->checkMagentoVersion(
            $this->productMetadata->getVersion(),
            $context->getVersion()
        );

        if ($this->installChecker->isInstalled() === false) {
            $this->installer->install($setup);
            return;
        }

        $this->upgrader->upgrade($setup);
    }

    private function checkMagentoVersion(string $magentoVersion, string $moduleVersion): void
    {
        if (!version_compare($magentoVersion, self::MINIMUM_REQUIRED_MAGENTO_VERSION, '>=')) {
            $this->maintenanceHelper->enableDueLowMagentoVersion();
            $this->throwVersionException($magentoVersion, $moduleVersion);
        }
    }

    private function throwVersionException(string $magentoVersion, string $moduleVersion): void
    {
        $message = sprintf(
            'Magento version %s is not compatible with M2E Pro version %s.',
            $magentoVersion,
            $moduleVersion
        );

        $message .= ' Please upgrade your Magento first or install an older M2E Pro version 1.35.0';

        throw new \RuntimeException($message);
    }
}
