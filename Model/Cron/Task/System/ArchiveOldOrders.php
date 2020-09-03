<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\System;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\System\ArchiveOldOrders
 */
class ArchiveOldOrders extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'system/archive_old_orders';

    /**
     * @var int (in seconds)
     */
    protected $interval = 3600;

    const MAX_ENTITIES_COUNT_FOR_ONE_TIME = 1000;

    const COUNT_EXCEEDS_TRIGGER = 100000;
    const DAYS_EXCEEDS_TRIGGER  = 180;

    //########################################

    protected function performActions()
    {
        $affectedOrders = $this->getAffectedOrdersGroupedByComponent();

        foreach ($this->getHelper('Component')->getEnabledComponents() as $component) {
            if (empty($affectedOrders[$component])) {
                continue;
            }

            $this->processComponentEntities($component, $affectedOrders[$component]);
        }

        return true;
    }

    //########################################

    protected function getAffectedOrdersGroupedByComponent()
    {
        $connection = $this->resource->getConnection() ;
        $firstAffectedId = $connection->select()
            ->from(
                $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_order'),
                ['id']
            )
            ->order('id DESC')
            ->limit(1, self::COUNT_EXCEEDS_TRIGGER)
            ->query()->fetchColumn();

        if ($firstAffectedId === false) {
            return [];
        }

        $archiveFromDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $archiveFromDate->modify('- ' .self::DAYS_EXCEEDS_TRIGGER. ' days');

        $queryStmt = $connection->select()
            ->from(
                $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_order'),
                ['id', 'component_mode']
            )
            ->where('id <= ?', (int)$firstAffectedId)
            ->where('create_date <= ?', $archiveFromDate->format('Y-m-d H:i:s'))
            ->limit(self::MAX_ENTITIES_COUNT_FOR_ONE_TIME)
            ->query();

        $orders = [];
        while ($row = $queryStmt->fetch()) {
            $orders[$row['component_mode']][] = (int)$row['id'];
        }

        return $orders;
    }

    protected function processComponentEntities($componentName, array $componentOrdersIds)
    {
        $connection = $this->resource->getConnection();
        /** @var \Ess\M2ePro\Helper\Module\Database\Structure $dbHelper */
        $dbHelper = $this->getHelper('Module_Database_Structure');

        $mainOrderTable = $dbHelper->getTableNameWithPrefix('m2epro_order');
        $componentOrderTable = $dbHelper->getTableNameWithPrefix('m2epro_' . $componentName . '_order');

        $queryStmt = $connection->select()
            ->from(['main_table' => $mainOrderTable])
            ->joinInner(
                ['second_table' => $componentOrderTable],
                'second_table.order_id = main_table.id'
            )
            ->where('main_table.id IN (?)', $componentOrdersIds)
            ->query();

        $insertsData = [];

        while ($orderRow = $queryStmt->fetch()) {
            $insertsData[$orderRow['id']] = [
                'name' => 'Order',
                'origin_id' => $orderRow['id'],
                'data' => [
                    'order_data' => $orderRow
                ],
                'create_date' => $this->getHelper('Data')->getCurrentGmtDate()
            ];
        }

        $mainOrderItemTable = $dbHelper->getTableNameWithPrefix('m2epro_order_item');
        $componentOrderItemTable = $dbHelper->getTableNameWithPrefix('m2epro_' . $componentName . '_order_item');

        $queryStmt = $connection->select()
            ->from(['main_table' => $mainOrderItemTable])
            ->joinInner(
                ['second_table' => $componentOrderItemTable],
                'second_table.order_item_id = main_table.id'
            )
            ->where('main_table.order_id IN (?)', $componentOrdersIds)
            ->query();

        $orderItemsIds = [];

        while ($itemRow = $queryStmt->fetch()) {
            if (!isset($insertsData[$itemRow['order_id']])) {
                continue;
            }

            $insertsData[$itemRow['order_id']]['data']['order_item_data'][$itemRow['id']] = $itemRow;
            $orderItemsIds[] = (int)$itemRow['id'];
        }

        if (empty($insertsData)) {
            return;
        }

        foreach ($insertsData as $key => &$data) {
            $data['data'] = $this->getHelper('Data')->jsonEncode($data['data']);
        }

        unset($data);

        foreach (array_chunk($insertsData, 200) as $dataPart) {
            $connection->insertMultiple($dbHelper->getTableNameWithPrefix('m2epro_archived_entity'), $dataPart);
        }

        $connection->delete($mainOrderTable, ['id IN (?)' => $componentOrdersIds]);
        $connection->delete($componentOrderTable, ['order_id IN (?)' => $componentOrdersIds]);

        $connection->delete($mainOrderItemTable, ['id IN (?)' => $orderItemsIds]);
        $connection->delete($componentOrderItemTable, ['order_item_id IN (?)' => $orderItemsIds]);
    }

    //########################################
}
