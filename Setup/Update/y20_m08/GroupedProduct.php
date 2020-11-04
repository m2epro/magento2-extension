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

    //########################################
}
