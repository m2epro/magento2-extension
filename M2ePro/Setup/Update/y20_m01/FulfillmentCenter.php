<?php

namespace Ess\M2ePro\Setup\Update\y20_m01;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m01\FulfillmentCenter
 */
class FulfillmentCenter extends AbstractFeature
{
    //########################################

    /**
     * {@inheritDoc}
     */
    public function getBackupTables()
    {
        return [
            'amazon_order_item'
        ];
    }

    /**
     * {@inheritDoc}
     * @throws \Ess\M2ePro\Model\Exception\Setup
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute()
    {
        $this->getTableModifier('amazon_order_item')
            ->addColumn('fulfillment_center_id', 'VARCHAR(10)', 'NULL', 'qty_purchased');

        //----------------------------------------

        $ordersStmt = $this->getConnection()
            ->select()
            ->from(
                ['o' => $this->getFullTableName('order')],
                ['id', 'additional_data']
            )
            ->joinLeft(
                ['oi' => $this->getFullTableName('order_item')],
                'o.id = oi.order_id',
                ['order_items_ids' => "GROUP_CONCAT(oi.id SEPARATOR ',')"]
            )
            ->where('o.component_mode = ?', 'amazon')
            ->where('o.additional_data LIKE ?', '%"fulfillment_center_id":%')
            ->group('o.id')
            ->query();

        while ($row = $ordersStmt->fetch()) {
            $additionalData = (array)json_decode($row['additional_data'], true);
            if (empty($additionalData['fulfillment_details']['fulfillment_center_id'])) {
                continue;
            }
            $fulfilmentCenterId = $additionalData['fulfillment_details']['fulfillment_center_id'];
            unset($additionalData['fulfillment_details']['fulfillment_center_id']);

            $this->getConnection()->update(
                $this->getFullTableName('amazon_order_item'),
                ['fulfillment_center_id' => $fulfilmentCenterId],
                ['order_item_id IN (?)' => explode(',', $row['order_items_ids'])]
            );

            $this->getConnection()->update(
                $this->getFullTableName('order'),
                ['additional_data' => json_encode($additionalData)],
                ['id = ?' => (int)$row['id']]
            );
        }
    }

    //########################################
}
