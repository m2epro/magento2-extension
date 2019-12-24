<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Setup\Upgrade;

use Ess\M2ePro\Model\AbstractModel;
use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;
use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Ess\M2ePro\Model\Setup\Upgrade\Entity\Factory;
use Magento\Framework\Module\Setup;

/**
 * Class \Ess\M2ePro\Model\Setup\Upgrade\Manager
 */
class Manager extends AbstractModel
{
    private $upgradeFactory;

    private $activeRecordFactory;

    private $installer;

    private $versionFrom;

    private $versionTo;

    /** @var AbstractConfig */
    private $configObject = null;

    /** @var AbstractFeature[] $featuresObjects */
    private $featuresObjects = [];

    /** @var Backup $backupObject */
    private $backupObject = null;

    //########################################

    public function __construct(
        Factory $upgradeFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        Setup $installer,
        $versionFrom,
        $versionTo,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->upgradeFactory = $upgradeFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->installer = $installer;

        $this->versionFrom = $versionFrom;
        $this->versionTo   = $versionTo;

        $this->configObject = $this->upgradeFactory->getConfigObject($this->versionFrom, $this->versionTo);

        $backupTables = [];

        foreach ($this->configObject->getFeaturesList() as $featureName) {
            $featureObject = $this->upgradeFactory->getFeatureObject(
                $featureName,
                $this->versionFrom,
                $this->versionTo
            );

            $backupTables = array_merge($backupTables, $featureObject->getBackupTables());

            $this->featuresObjects[] = $featureObject;
        }

        $this->backupObject = $modelFactory->getObject('Setup_Upgrade_Backup', [
            'versionFrom' => $this->versionFrom,
            'versionTo'   => $this->versionTo,
            'tablesList'  => $backupTables,
            'installer'   => $this->installer,
        ]);

        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    /**
     * @return Backup
     */
    public function getBackupObject()
    {
        return $this->backupObject;
    }

    //########################################

    public function process()
    {
        foreach ($this->featuresObjects as $featuresObject) {
            $featuresObject->execute();
        }
    }

    //########################################
}
