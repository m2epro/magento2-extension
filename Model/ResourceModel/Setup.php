<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel;

use Magento\Framework\DB\Ddl\Table;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Setup
 */
class Setup extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    const LONG_COLUMN_SIZE = 16777217;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_setup', 'id');
    }

    //########################################

    /**
     * @param $versionFrom
     * @param $versionTo
     * @return \Ess\M2ePro\Model\Setup
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function initCurrentSetupObject($versionFrom, $versionTo)
    {
        if (!$this->getHelper('Module_Database_Structure')->isTableExists('m2epro_setup')) {
            $this->initTable();
        }

        $collection = $this->activeRecordFactory->getObject('Setup')->getCollection();

        empty($versionFrom) ? $collection->addFieldToFilter('version_from', ['null' => true])
                            : $collection->addFieldToFilter('version_from', $versionFrom);

        $collection->addFieldToFilter('version_to', $versionTo);
        $collection->getSelect()->limit(1);

        /** @var \Ess\M2ePro\Model\Setup $setupObject */
        $setupObject = $collection->getFirstItem();

        if (!$setupObject->getId()) {
            $setupObject->setData(
                [
                    'version_from' => empty($versionFrom) ? null : $versionFrom,
                    'version_to'   => $versionTo,
                    'is_backuped'  => 0,
                    'is_completed' => 0,
                ]
            );
            $setupObject->save();
        }

        return $setupObject;
    }

    protected function initTable()
    {
        $setupTable = $this->getConnection()
            ->newTable($this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_setup'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'version_from',
                Table::TYPE_TEXT,
                32,
                ['default' => null]
            )
            ->addColumn(
                'version_to',
                Table::TYPE_TEXT,
                32,
                ['default' => null]
            )
            ->addColumn(
                'is_backuped',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'is_completed',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'profiler_data',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['default' => null]
            )
            ->addColumn(
                'update_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'create_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addIndex('version_from', 'version_from')
            ->addIndex('version_to', 'version_to')
            ->addIndex('is_backuped', 'is_backuped')
            ->addIndex('is_completed', 'is_completed')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');

        $this->getConnection()->createTable($setupTable);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Setup[]
     */
    public function getNotCompletedUpgrades()
    {
        if (!$this->getHelper('Module_Database_Structure')->isTableExists('m2epro_setup')) {
            return  [];
        }

        $collection = $this->activeRecordFactory->getObject('Setup')->getCollection();
        $collection->addFieldToFilter('version_from', ['notnull' => true]);
        $collection->addFieldToFilter('version_to', ['notnull' => true]);
        $collection->addFieldToFilter('is_backuped', 1);
        $collection->addFieldToFilter('is_completed', 0);

        return $collection->getItems();
    }

    /**
     * @return \Ess\M2ePro\Model\Setup
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getMaxCompletedItem()
    {
        $collection = $this->activeRecordFactory->getObject('Setup')->getCollection();
        $collection->addFieldToFilter('is_completed', 1);

        /** @var \Ess\M2ePro\Model\Setup[] $completedItems */
        $completedItems = $collection->getItems();

        /** @var \Ess\M2ePro\Model\Setup $maxCompletedItem */
        $maxCompletedItem = null;

        foreach ($completedItems as $completedItem) {
            if ($maxCompletedItem === null) {
                $maxCompletedItem = $completedItem;
                continue;
            }

            if (version_compare($maxCompletedItem->getVersionTo(), $completedItem->getVersionTo(), '>')) {
                continue;
            }

            $maxCompletedItem = $completedItem;
        }

        return $maxCompletedItem;
    }

    //########################################

    /**
     * @return null|string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getLastUpgradeDate()
    {
        if (!$this->getHelper('Module_Database_Structure')->isTableExists('m2epro_setup')) {
            return null;
        }

        $collection = $this->activeRecordFactory->getObject('Setup')->getCollection();

        /** @var \Ess\M2ePro\Model\Setup $setupObject */
        $setupObject = $collection->getLastItem();
        if (!$setupObject->getId()) {
            return null;
        }

        return $setupObject->getCreateDate();
    }

    //########################################
}
