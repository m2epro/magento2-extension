<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_1__v1_3_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Magento\Framework\DB\Ddl\Table;

class AmazonBusiness extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return [
            'module_config',
            'amazon_marketplace',
            'amazon_account',
            'amazon_listing_product',
            'amazon_template_selling_format',
            'amazon_template_synchronization'
        ];
    }

    public function execute()
    {
        $this->getConfigModifier('module')->insert('/amazon/business/', 'mode', '0', '0 - disable, \r\n1 - enable');

        //########################################

        $this->getTableModifier('amazon_marketplace')
            ->addColumn(
                'is_business_available', 'SMALLINT(5) UNSIGNED NOT NULL', 0,
                'is_merchant_fulfillment_available', true, false
            )
            ->addColumn(
                'is_vat_calculation_service_available', 'SMALLINT(5) UNSIGNED NOT NULL', 0,
                'is_business_available', true, false
            )
            ->addColumn(
                'is_product_tax_code_policy_available', 'SMALLINT(5) UNSIGNED NOT NULL', 0,
                'is_vat_calculation_service_available', true, false
            )
            ->commit();

        $this->getConnection()->update(
            $this->getFullTableName('amazon_marketplace'),
            array('is_business_available' => 1),
            array('marketplace_id IN (?)' => array(25, 28, 29)) // DE, UK, US
        );

        $this->getConnection()->update(
            $this->getFullTableName('amazon_marketplace'),
            array('is_vat_calculation_service_available' => 1),
            array('marketplace_id IN (?)' => array(25, 26, 28, 30, 31)) // Europe
        );

        $this->getConnection()->update(
            $this->getFullTableName('amazon_marketplace'),
            array('is_product_tax_code_policy_available' => 1),
            array('marketplace_id IN (?)' => array(25, 28)) // DE, UK
        );

        $this->getTableModifier('amazon_account')
            ->addColumn(
                'is_vat_calculation_service_enabled', 'SMALLINT(5) UNSIGNED NOT NULL', 0,
                'magento_orders_settings', false, false
            )
            ->addColumn(
                'is_magento_invoice_creation_disabled', 'SMALLINT(5) UNSIGNED NOT NULL', 0,
                'is_vat_calculation_service_enabled', false, false
            )
            ->commit();

        //########################################

        $this->getConnection()->dropTable($this->getFullTableName('indexer_listing_product_variation_parent'));

        $amazonTemplateProductTaxCodeTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_template_product_tax_code')
        )
            ->addColumn(
                'id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'title', Table::TYPE_TEXT, 255,
                ['nullable' => false]
            )
            ->addColumn(
                'product_tax_code_mode', Table::TYPE_SMALLINT, NULL,
                ['nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'product_tax_code_value', Table::TYPE_TEXT, 255,
                ['default' => NULL]
            )
            ->addColumn(
                'product_tax_code_attribute', Table::TYPE_TEXT, 255,
                ['default' => NULL]
            )
            ->addColumn(
                'update_date', Table::TYPE_DATETIME, NULL,
                ['default' => NULL]
            )
            ->addColumn(
                'create_date', Table::TYPE_DATETIME, NULL,
                ['default' => NULL]
            )
            ->addIndex('title', 'title')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonTemplateProductTaxCodeTable);

        $amazonTemplateSellingFormatBusinessDiscountTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_template_selling_format_business_discount')
        )
            ->addColumn(
                'id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'template_selling_format_id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'qty', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'mode', Table::TYPE_SMALLINT, NULL,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'attribute', Table::TYPE_TEXT, 255,
                ['default' => NULL]
            )
            ->addColumn(
                'coefficient', Table::TYPE_TEXT, 255,
                ['default' => NULL]
            )
            ->addIndex('template_selling_format_id', 'template_selling_format_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonTemplateSellingFormatBusinessDiscountTable);

        $ebayIndexerListingProductVariationParentTable = $this->getConnection()->newTable(
            $this->getFullTableName('ebay_indexer_listing_product_variation_parent')
        )
            ->addColumn(
                'listing_product_id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'listing_id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'component_mode', Table::TYPE_TEXT, 10,
                ['default' => NULL]
            )
            ->addColumn(
                'min_price', Table::TYPE_DECIMAL, [12, 4],
                ['unsigned' => true, 'nullable' => false, 'default' => '0.0000']
            )
            ->addColumn(
                'max_price', Table::TYPE_DECIMAL, [12, 4],
                ['unsigned' => true, 'nullable' => false, 'default' => '0.0000']
            )
            ->addColumn(
                'create_date', Table::TYPE_DATETIME, NULL,
                ['nullable' => false]
            )
            ->addIndex('listing_id', 'listing_id')
            ->addIndex('component_mode', 'component_mode')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayIndexerListingProductVariationParentTable);

        $amazonIndexerListingProductVariationParentTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_indexer_listing_product_variation_parent')
        )
            ->addColumn(
                'listing_product_id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'primary' => true, 'nullable' => false]
            )
            ->addColumn(
                'listing_id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'component_mode', Table::TYPE_TEXT, 10,
                ['default' => NULL]
            )
            ->addColumn(
                'min_regular_price', Table::TYPE_DECIMAL, [12, 4],
                ['unsigned' => true, 'default' => NULL]
            )
            ->addColumn(
                'max_regular_price', Table::TYPE_DECIMAL, [12, 4],
                ['unsigned' => true, 'default' => NULL]
            )
            ->addColumn(
                'min_business_price', Table::TYPE_DECIMAL, [12, 4],
                ['unsigned' => true, 'default' => NULL]
            )
            ->addColumn(
                'max_business_price', Table::TYPE_DECIMAL, [12, 4],
                ['unsigned' => true, 'default' => NULL]
            )
            ->addColumn(
                'create_date', Table::TYPE_DATETIME, NULL,
                ['nullable' => false]
            )
            ->addIndex('listing_id', 'listing_id')
            ->addIndex('component_mode', 'component_mode')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonIndexerListingProductVariationParentTable);

        //########################################

        $this->getTableModifier('amazon_listing_product')
            ->renameColumn(
                'online_price', 'online_regular_price', true, false
            )
            ->renameColumn(
                'online_sale_price', 'online_regular_sale_price', true, false
            )
            ->renameColumn(
                'online_sale_price_start_date', 'online_regular_sale_price_start_date', false, false
            )
            ->renameColumn(
                'online_sale_price_end_date', 'online_regular_sale_price_end_date', false, false
            )
            ->addColumn(
                'template_product_tax_code_id', 'INT(10) UNSIGNED', 'NULL', 'template_shipping_override_id', true, false
            )
            ->addColumn(
                'online_business_price', 'DECIMAL(12, 4)', 'NULL', 'online_regular_sale_price_end_date', true, false
            )
            ->addColumn(
                'online_business_discounts', 'TEXT', 'NULL', 'online_business_price', false, false
            )
            ->commit();

        //########################################

        $this->getTableModifier('amazon_template_selling_format')
            ->renameColumn(
                'price_mode', 'regular_price_mode', false, false
            )
            ->renameColumn(
                'price_custom_attribute', 'regular_price_custom_attribute', false, false
            )
            ->renameColumn(
                'price_coefficient', 'regular_price_coefficient', false, false
            )
            ->renameColumn(
                'map_price_mode', 'regular_map_price_mode', false, false
            )
            ->renameColumn(
                'map_price_custom_attribute', 'regular_map_price_custom_attribute', false, false
            )
            ->renameColumn(
                'sale_price_mode', 'regular_sale_price_mode', false, false
            )
            ->renameColumn(
                'sale_price_custom_attribute', 'regular_sale_price_custom_attribute', false, false
            )
            ->renameColumn(
                'sale_price_coefficient', 'regular_sale_price_coefficient', false, false
            )
            ->renameColumn(
                'price_variation_mode', 'regular_price_variation_mode', false, false
            )
            ->renameColumn(
                'sale_price_start_date_mode', 'regular_sale_price_start_date_mode', false, false
            )
            ->renameColumn(
                'sale_price_start_date_value', 'regular_sale_price_start_date_value', false, false
            )
            ->renameColumn(
                'sale_price_start_date_custom_attribute', 'regular_sale_price_start_date_custom_attribute', false, false
            )
            ->renameColumn(
                'sale_price_end_date_mode', 'regular_sale_price_end_date_mode', false, false
            )
            ->renameColumn(
                'sale_price_end_date_value', 'regular_sale_price_end_date_value', false, false
            )
            ->renameColumn(
                'sale_price_end_date_custom_attribute', 'regular_sale_price_end_date_custom_attribute', false, false
            )
            ->renameColumn(
                'price_vat_percent', 'regular_price_vat_percent', false, false
            )
            ->addColumn(
                'is_regular_customer_allowed', 'SMALLINT(5) UNSIGNED NOT NULL', 1, 'qty_max_posted_value', false, false
            )
            ->addColumn(
                'is_business_customer_allowed', 'SMALLINT(5) UNSIGNED NOT NULL', 0,
                'is_regular_customer_allowed', false, false
            )
            ->addColumn(
                'business_price_mode', 'SMALLINT(5) UNSIGNED NOT NULL', NULL, 'regular_price_vat_percent', false, false
            )
            ->addColumn(
                'business_price_custom_attribute', 'VARCHAR(255) NOT NULL', NULL, 'business_price_mode', false, false
            )
            ->addColumn(
                'business_price_coefficient', 'VARCHAR(255) NOT NULL', NULL,
                'business_price_custom_attribute', false, false
            )
            ->addColumn(
                'business_price_variation_mode', 'SMALLINT(5) UNSIGNED NOT NULL',
                NULL, 'business_price_coefficient', false, false
            )
            ->addColumn(
                'business_price_vat_percent', 'FLOAT', 'NULL', 'business_price_variation_mode', false, false
            )
            ->addColumn(
                'business_discounts_mode', 'SMALLINT(5) UNSIGNED NOT NULL',
                NULL, 'business_price_vat_percent', false, false
            )
            ->addColumn(
                'business_discounts_tier_coefficient', 'VARCHAR(255) NOT NULL',
                NULL, 'business_discounts_mode', false, false
            )
            ->addColumn(
                'business_discounts_tier_customer_group_id', 'INT(10) UNSIGNED',
                'NULL', 'business_discounts_tier_coefficient', false, false
            )
            ->dropIndex('price_variation_mode', false)
            ->commit();

        $this->getTableModifier('amazon_template_selling_format')
            ->changeColumn('regular_price_vat_percent', 'FLOAT', 'NULL');

        //########################################

        $this->getTableModifier('amazon_order')
            ->addColumn('is_business', 'SMALLINT(5) UNSIGNED NOT NULL', '0', 'is_prime', true);

        //########################################

        $this->getTableModifier('amazon_template_synchronization')->addColumn(
            'revise_change_product_tax_code_template', 'SMALLINT(5) UNSIGNED NOT NULL', NULL,
            'revise_change_shipping_template'
        );

        //########################################

        $processingTable = $this->getFullTableName('processing');

        $processings = $this->getConnection()->query("
    SELECT * FROM {$processingTable}
    WHERE `model` LIKE 'Amazon\Connector\Product\%';
")->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($processings as $processing) {
            if (empty($processing['params'])) {
                continue;
            }

            $params = (array)@json_decode($processing['params'], true);
            if (!isset($params['responser_params']['products'])) {
                continue;
            }

            $productsData = (array)$params['responser_params']['products'];

            $isDataChanged = false;

            foreach ($productsData as $productId => &$productData) {
                if (!isset($productData['configurator']['allowed_data_types'])) {
                    continue;
                }

                $allowedDataTypes = $productData['configurator']['allowed_data_types'];

                $priceDataTypeIndex = array_search('price', $allowedDataTypes);

                if ($priceDataTypeIndex === false) {
                    continue;
                }

                unset($allowedDataTypes[$priceDataTypeIndex]);
                $allowedDataTypes[] = 'regular_price';

                $productData['configurator']['allowed_data_types'] = $allowedDataTypes;

                $isDataChanged = true;
            }

            if (!$isDataChanged) {
                continue;
            }

            $params['responser_params']['products'] = $productsData;

            $this->getConnection()->update(
                $processingTable,
                array('params' => json_encode($params)),
                array('id = ?' => $processing['id'])
            );
        }

        //########################################
    }

    //########################################
}