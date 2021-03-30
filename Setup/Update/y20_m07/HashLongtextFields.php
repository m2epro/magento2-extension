<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m07;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m07\HashLongtextFields
 */
class HashLongtextFields extends AbstractFeature
{
    //########################################

    public function execute()
    {
        if (!$this->isTableMigratedToMd5('amazon_listing_product', 'online_details_data')) {
            $this->installer->run(
                <<<SQL
UPDATE `{$this->getFullTableName('amazon_listing_product')}`
SET
`online_details_data` = md5(online_details_data),
`online_images_data` = md5(online_images_data);
SQL
            );
        }

        if (!$this->isTableSchemaMigrated('amazon_listing_product', 'online_details_data')) {
            $this->getTableModifier('amazon_listing_product')
                ->changeColumn('online_details_data', 'VARCHAR(40) DEFAULT NULL', null, null, false)
                ->changeColumn('online_images_data', 'VARCHAR(40) DEFAULT NULL', null, null, false)
                ->commit();
        }

        if (!$this->isTableMigratedToMd5('ebay_listing_product', 'online_description')) {
            $this->installer->run(
                <<<SQL
UPDATE `{$this->getFullTableName('ebay_listing_product')}`
SET
`online_description` = md5(online_description),
`online_images` = md5(online_images),
`online_shipping_data` = md5(online_shipping_data),
`online_payment_data` = md5(online_payment_data),
`online_return_data` = md5(online_return_data),
`online_other_data` = md5(online_other_data);
SQL
            );
        }

        if (!$this->isTableSchemaMigrated('ebay_listing_product', 'online_description')) {
            $this->getTableModifier('ebay_listing_product')
                ->changeColumn('online_description', 'VARCHAR(40) DEFAULT NULL', null, null, false)
                ->changeColumn('online_images', 'VARCHAR(40) DEFAULT NULL', null, null, false)
                ->changeColumn('online_shipping_data', 'VARCHAR(40) DEFAULT NULL', null, null, false)
                ->changeColumn('online_payment_data', 'VARCHAR(40) DEFAULT NULL', null, null, false)
                ->changeColumn('online_return_data', 'VARCHAR(40) DEFAULT NULL', null, null, false)
                ->changeColumn('online_other_data', 'VARCHAR(40) DEFAULT NULL', null, null, false)
                ->commit();
        }

        if (!$this->isTableMigratedToMd5('walmart_listing_product', 'online_details_data')) {
            $this->installer->run(
                <<<SQL
UPDATE `{$this->getFullTableName('walmart_listing_product')}`
SET
`online_promotions` = md5(online_promotions),
`online_details_data` = md5(online_details_data);
SQL
            );
        }

        if (!$this->isTableSchemaMigrated('walmart_listing_product', 'online_details_data')) {
            $this->getTableModifier('walmart_listing_product')
                ->changeColumn('online_promotions', 'VARCHAR(40) DEFAULT NULL', null, null, false)
                ->changeColumn('online_details_data', 'VARCHAR(40) DEFAULT NULL', null, null, false)
                ->commit();
        }
    }

    //########################################

    private function isTableMigratedToMd5($tableName, $column)
    {
        $value = $this->installer->getConnection()
            ->select()
            ->from($this->getFullTableName($tableName), $column)
            ->where("LENGTH(`{$column}`) <> 32")
            ->limit(1)
            ->query()
            ->fetchColumn();

        if ($value !== false && !ctype_xdigit($value)) {
            return false;
        }

        return true;
    }

    private function isTableSchemaMigrated($tableName, $column)
    {
        $describe = $this->installer->getConnection()->describeTable($this->getFullTableName($tableName));
        $prop = $describe[$column];

        if ($prop['DATA_TYPE'] != 'VARCHAR' && (int)$prop['LENGTH'] != 40) {
            return false;
        }

        return true;
    }

    //########################################
}
