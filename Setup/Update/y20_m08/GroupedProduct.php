<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m08;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class Ess\M2ePro\Setup\Update\y20_m08\GroupedProduct
 */
class GroupedProduct extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConfigModifier('module')->insert(
            '/general/configuration/',
            'grouped_product_mode',
            '0' // GROUPED_PRODUCT_MODE_OPTIONS
        );

        // ----------------------------------------

        $this->getTableModifier('order_item')
            ->addColumn('additional_data', 'TEXT', 'NULL', 'qty_reserved', false);

        // ----------------------------------------

        $this->getTableModifier('ebay_item')
            ->addColumn('additional_data', 'TEXT', 'NULL', 'variations', false);

        $this->getTableModifier('amazon_item')
            ->addColumn('additional_data', 'TEXT', 'NULL', 'variation_channel_options', false);

        $this->getTableModifier('walmart_item')
            ->addColumn('additional_data', 'TEXT', 'NULL', 'variation_channel_options', false);

        // ----------------------------------------

        $this->processChannelItems();
        $this->processOrderItems();
    }

    private function processChannelItems()
    {
        $productsStmt = $this->getConnection()->select()
            ->from(
                $this->getFullTableName('listing_product'),
                ['id', 'additional_data']
            )
            ->joinLeft(
                $this->installer->getTable('catalog_product_entity'),
                'product_id = entity_id',
                ['entity_id']
            )
            ->where(
                'type_id = ?',
                'grouped'
            )
            ->query();

        $productIds = [];
        while ($row = $productsStmt->fetch()) {
            $additionalData = (array)json_decode($row['additional_data'], true);
            $additionalData['grouped_product_mode'] = 0; // GROUPED_PRODUCT_MODE_OPTIONS
            $additionalData = json_encode($additionalData);

            $this->getConnection()->update(
                $this->getFullTableName('listing_product'),
                ['additional_data' => $additionalData],
                ['id = ?' => (int)$row['id']]
            );

            $productIds[] = (int)$row['entity_id'];
        }

        // ----------------------------------------

        $additionalData = [];
        $additionalData['grouped_product_mode'] = 0; // GROUPED_PRODUCT_MODE_OPTIONS
        $additionalData = json_encode($additionalData);

        $this->getConnection()->update(
            $this->getFullTableName('ebay_item'),
            ['additional_data' => $additionalData],
            ['product_id IN (?)' => $productIds]
        );

        $this->getConnection()->update(
            $this->getFullTableName('amazon_item'),
            ['additional_data' => $additionalData],
            ['product_id IN (?)' => $productIds]
        );

        $this->getConnection()->update(
            $this->getFullTableName('walmart_item'),
            ['additional_data' => $additionalData],
            ['product_id IN (?)' => $productIds]
        );
    }

    private function processOrderItems()
    {
        $data = [];
        $orderIds = [];
        $magentoOrderIds = [];

        $walmartOrderStmt = $this->getConnection()
            ->select()
            ->from(
                ['wo' => $this->getFullTableName('walmart_order')],
                ['order_id']
            )
            ->joinLeft(
                ['o' => $this->getFullTableName('order')],
                'wo.order_id = o.id',
                ['magento_order_id']
            )
            ->where('wo.status IN (?)', [0, 1])
            ->where('o.magento_order_id != ?', null)
            ->query();

        while ($row = $walmartOrderStmt->fetch()) {
            $data[] = [
                'order_id'         => $row['order_id'],
                'magento_order_id' => $row['magento_order_id'],
                'items'            => []
            ];
            $orderIds[] = $row['order_id'];
            $magentoOrderIds[] = $row['magento_order_id'];
        }

        // ----------------------------------------

        $orderItemStmt = $this->getConnection()->select()
            ->from(
                $this->getFullTableName('order_item'),
                ['id', 'order_id']
            )
            ->joinLeft(
                $this->getFullTableName('walmart_order_item'),
                'id = order_item_id',
                ['walmart_order_item_id']
            )
            ->where('order_id IN (?)', $orderIds)
            ->query();

        while ($row = $orderItemStmt->fetch()) {
            foreach ($data as $index => $order) {
                if ($order['order_id'] == $row['order_id']) {
                    $data[$index]['items'][$row['id']] = $row['walmart_order_item_id'];
                }
            }
        }

        // ----------------------------------------

        $magentoOrderItemStmt = $this->getConnection()->select()
            ->from(
                $this->installer->getTable('sales_order_item'),
                ['item_id', 'order_id', 'additional_data']
            )
            ->where('order_id IN (?)', $magentoOrderIds)
            ->query();

        while ($row = $magentoOrderItemStmt->fetch()) {
            $additionalData = (array)json_decode($row['additional_data'], true);
            $orderItemId = $additionalData['m2epro_extension']['items'][0]['order_item_id'];

            foreach ($data as $order) {
                if ($order['magento_order_id'] == $row['order_id'] && isset($order['items'][$orderItemId])) {
                    $additionalData['m2epro_extension']['items'][0]['order_item_id'] = $order['items'][$orderItemId];

                    $this->getConnection()->update(
                        $this->installer->getTable('sales_order_item'),
                        ['additional_data' => json_encode($additionalData)],
                        [
                            'item_id = ?'  => $row['item_id'],
                            'order_id = ?' => $row['order_id']
                        ]
                    );

                    break;
                }
            }
        }
    }

    //########################################
}
