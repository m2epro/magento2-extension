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
    /** @var \Ess\M2ePro\Model\Setup\InstallChecker */
    private $installChecker;

    /** @var \Ess\M2ePro\Model\Setup\Installer */
    private $installer;

    /** @var \Ess\M2ePro\Model\Setup\Upgrader */
    private $upgrader;

    /**
     * @param \Ess\M2ePro\Model\Setup\InstallChecker $installChecker
     * @param \Ess\M2ePro\Model\Setup\Installer      $installer
     * @param \Ess\M2ePro\Model\Setup\Upgrader       $upgrader
     */
    public function __construct(
        \Ess\M2ePro\Model\Setup\InstallChecker $installChecker,
        \Ess\M2ePro\Model\Setup\Installer $installer,
        \Ess\M2ePro\Model\Setup\Upgrader $upgrader
    ) {
        $this->installChecker = $installChecker;
        $this->installer = $installer;
        $this->upgrader = $upgrader;
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
        if ($this->installChecker->isInstalled() === false) {
            $this->installer->install($setup);
            return;
        }

        $this->upgrader->upgrade($setup);
    }
}
