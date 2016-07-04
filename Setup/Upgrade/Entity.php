<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade;

use Ess\M2ePro\Model\Config\Manager\Module;
use Ess\M2ePro\Setup\Tables;
use Ess\M2ePro\Setup\Upgrade\Source\AbstractFeature;
use Ess\M2ePro\Setup\Upgrade\Source\Factory;
use Ess\M2ePro\Setup\Upgrade\BackupFactory;
use Magento\Framework\Module\Setup;

class Entity
{
    const BACKUP_TABLE_NAME_SUFFIX = '__backup';

    protected $sourceFactory;

    protected $backupFactory;

    protected $helperFactory;

    protected $installer;

    protected $tablesObject;

    protected $moduleConfig;

    protected $versionFrom;

    protected $versionTo;

    protected $mode;

    /** @var Source\AbstractConfig */
    private $configObject = NULL;

    /** @var AbstractFeature[] $featuresObjects */
    private $featuresObjects = [];

    /** @var Backup $backup */
    private $backup = NULL;

    private $setupRow = NULL;

    //########################################

    public function __construct(
        Factory $sourceFactory,
        BackupFactory $backupFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        Setup $installer,
        Tables $tablesObject,
        Module $moduleConfig,
        $versionFrom, $versionTo
    ) {
        $this->sourceFactory = $sourceFactory;
        $this->backupFactory = $backupFactory;
        $this->helperFactory = $helperFactory;
        $this->installer     = $installer;
        $this->tablesObject  = $tablesObject;
        $this->moduleConfig  = $moduleConfig;

        $this->versionFrom = $versionFrom;
        $this->versionTo   = $versionTo;

        $this->configObject = $this->sourceFactory->getConfigObject($this->versionFrom, $this->versionTo);

        $backupTables = [];

        foreach ($this->configObject->getFeaturesList() as $featureName) {
            $featureObject = $this->sourceFactory->getFeatureObject(
                $this->versionFrom, $this->versionTo, $featureName, $this->installer
            );

            $backupTables = array_merge($backupTables, $featureObject->getBackupTables());

            $this->featuresObjects[] = $featureObject;
        }

        $this->backup = $this->backupFactory->create([
            'versionFrom' => $this->versionFrom,
            'versionTo'   => $this->versionTo,
            'tablesList'  => $backupTables,
            'installer'   => $this->installer,
        ]);
    }

    //########################################

    public function getVersionFrom()
    {
        return $this->versionFrom;
    }

    public function getVersionTo()
    {
        return $this->versionTo;
    }

    /**
     * @return Backup
     */
    public function getBackup()
    {
        return $this->backup;
    }

    //########################################

    public function process()
    {
        if ($this->isAlreadyCompleted()) {
            throw new \Ess\M2ePro\Model\Exception(
                sprintf('Upgrade %s - %s already processed.', $this->versionFrom, $this->versionTo)
            );
        }

        $this->setSetupRow([]);

        $this->getBackup()->remove();
        $this->getBackup()->create();
        $this->setAlreadyBackuped();

        foreach ($this->featuresObjects as $featuresObject) {
            $featuresObject->execute();
        }

        $this->setAlreadyCompleted();
        $this->getBackup()->remove();
    }

    //########################################

    private function setAlreadyCompleted()
    {
        $this->setSetupRow(['is_completed' => 1]);
    }

    private function isAlreadyCompleted()
    {
        $setupRow = $this->getSetupRow();
        if (!isset($setupRow['is_completed'])) {
            return false;
        }

        return (bool)$setupRow['is_completed'];
    }

    // ---------------------------------------

    private function setAlreadyBackuped()
    {
        $this->setSetupRow(['is_backuped' => 1]);
    }

    //########################################

    private function getSetupRow()
    {
        if (!is_null($this->setupRow)) {
            return $this->setupRow;
        }

        $select = $this->installer->getConnection()->select();
        $select->from($this->tablesObject->getFullName('setup'));
        $select->where('version_from = ?', $this->versionFrom);
        $select->where('version_to = ?', $this->versionTo);

        return $this->setupRow = $this->installer->getConnection()->fetchRow($select);
    }

    private function setSetupRow(array $data)
    {
        $this->setupRow = NULL;

        $data['update_date'] = $this->helperFactory->getObject('Data')->getCurrentGmtDate();

        if (!empty($this->getSetupRow())) {
            $this->installer->getConnection()->update(
                $this->tablesObject->getFullName('setup'),
                $data, ['version_from' => $this->versionFrom, 'version_to' => $this->versionTo]
            );
            return;
        }

        $data['version_from'] = $this->versionFrom;
        $data['version_to']   = $this->versionTo;
        $data['create_date']  = $data['update_date'];

        $this->installer->getConnection()->insert(
            $this->tablesObject->getFullName('setup'),
            $data
        );
    }

    //########################################
}