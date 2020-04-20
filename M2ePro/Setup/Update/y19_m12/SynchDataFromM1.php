<?php

namespace Ess\M2ePro\Setup\Update\y19_m12;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19_m12\SynchDataFromM1
 */
class SynchDataFromM1 extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('ebay_order')
            ->changeColumn('saved_amount', 'DECIMAL(12,4) UNSIGNED NOT NULL', '0.0000', null, false)
            ->commit();

        $this->getTableModifier('ebay_listing_product_variation')
            ->changeColumn('online_price', 'DECIMAL(12,4) UNSIGNED', 'NULL', null, false)
            ->commit();

        $this->getTableModifier('walmart_order_item')
            ->changeColumn('merged_walmart_order_item_ids', 'TEXT', 'NULL', null, false)
            ->commit();

        $this->getTableModifier('walmart_indexer_listing_product_variation_parent')
            ->changeColumn('min_price', 'DECIMAL(12,4) UNSIGNED NOT NULL', '0.0000', null, false)
            ->changeColumn('max_price', 'DECIMAL(12,4) UNSIGNED NOT NULL', '0.0000', null, false)
            ->changeColumn('create_date', 'DATETIME NOT NULL', null, null, false)
            ->dropColumn('component_mode', true, false)
            ->commit();

        $this->getTableModifier('amazon_template_product_tax_code')
            ->changeColumn('product_tax_code_mode', 'SMALLINT(6) NOT NULL', null, null, false)
            ->commit();

        $this->getTableModifier('ebay_indexer_listing_product_variation_parent')
            ->dropColumn('component_mode');

        $this->getTableModifier('amazon_indexer_listing_product_variation_parent')
            ->dropColumn('component_mode');

        $this->getTableModifier('ebay_template_selling_format')
            ->changeColumn('vat_percent', 'FLOAT(10,0) UNSIGNED NOT NULL', '0', null, false)
            ->commit();

        $this->getTableModifier('setup')
            ->changeColumn('version_to', 'VARCHAR(32)', 'NULL', null, false)
            ->changeColumn('profiler_data', 'LONGTEXT', null, null, false)
            ->commit();

        $this->getConnection()->update(
            $this->getFullTableName('marketplace'),
            ['group_title' => 'Australia Region'],
            ['id IN(?)' => [4, 35]]
        );

        $this->getTableModifier('ebay_marketplace')
            ->dropColumn('is_holiday_return', true, false)
            ->addColumn(
                'is_return_description',
                'SMALLINT(5) UNSIGNED NOT NULL',
                '0',
                'is_in_store_pickup',
                true,
                false
            )
            ->dropColumn('translation_service_mode', false, false)
            ->commit();

        $this->getConnection()->update(
            $this->getFullTableName('ebay_marketplace'),
            [
                'is_return_description' => 1
            ],
            ['marketplace_id IN(?)' => [8, 13, 7, 10, 5]]
        );

        $this->getConnection()->update(
            $this->getFullTableName('amazon_marketplace'),
            [
                'is_business_available'                => 1,
                'is_product_tax_code_policy_available' => 1
            ],
            ['marketplace_id IN(?)' => [26, 30, 31]]
        );

        $this->getConnection()->update(
            $this->getFullTableName('amazon_marketplace'),
            ['is_new_asin_available' => 1],
            ['marketplace_id = ?' => [34]]
        );

        $this->getConnection()->update(
            $this->getFullTableName('amazon_marketplace'),
            ['is_automatic_token_retrieving_available' => 1],
            ['marketplace_id = ?' => [35]]
        );
    }

    //########################################
}
