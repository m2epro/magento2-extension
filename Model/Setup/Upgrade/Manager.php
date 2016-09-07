<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Setup\Upgrade;

use Ess\M2ePro\Model\AbstractModel;
use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;
use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Ess\M2ePro\Model\Setup\Upgrade\Entity\Factory;
use Magento\Framework\Module\Setup;

class Manager extends AbstractModel
{
    private $upgradeFactory;

    private $activeRecordFactory;

    private $installer;

    private $versionFrom;

    private $versionTo;

    /** @var AbstractConfig */
    private $configObject = NULL;

    /** @var AbstractFeature[] $featuresObjects */
    private $featuresObjects = [];

    /** @var Backup $backupObject */
    private $backupObject = NULL;

    //########################################

    public function __construct(
        Factory $upgradeFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        Setup $installer,
        $versionFrom, $versionTo,
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
                $this->versionFrom, $this->versionTo, $featureName, $this->installer
            );

            $backupTables = array_merge($backupTables, $featureObject->getBackupTables());

            $this->featuresObjects[] = $featureObject;
        }

        $this->backupObject = $modelFactory->getObject('Setup\Upgrade\Backup', [
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