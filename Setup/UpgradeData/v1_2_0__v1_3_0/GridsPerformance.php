<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_2_0__v1_3_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Magento\Framework\DB\Ddl\Table;

class GridsPerformance extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['amazon_listing_product'];
    }

    public function execute()
    {
        $this->getTableModifier('amazon_listing_product')
            ->addColumn('is_repricing', 'SMALLINT(5) UNSIGNED NOT NULL', 0, 'online_qty', true);

        //----------------------------------------

        $this->getConnection()->exec(<<<SQL

UPDATE `{$this->getFullTableName('amazon_listing_product')}` `malp`
LEFT JOIN `{$this->getFullTableName('amazon_listing_product_repricing')}` `malpr`
    ON `malp`.`listing_product_id` = `malpr`.`listing_product_id`
SET `is_repricing` = 1
WHERE `malpr`.`listing_product_id` IS NOT NULL;

SQL
        );

        $this->getConnection()->exec(<<<SQL

UPDATE `{$this->getFullTableName('amazon_listing_product')}` `malp`
LEFT JOIN (
    SELECT
       `malp`.`variation_parent_id`,
       COUNT(*) AS `repricing_count`
    FROM `{$this->getFullTableName('amazon_listing_product')}` as `malp`
    WHERE `malp`.`variation_parent_id` IS NOT NULL AND `malp`.`is_repricing` = 1
    GROUP BY `malp`.`variation_parent_id`
) as `temp`
ON `malp`.`listing_product_id` = `temp`.`variation_parent_id`
SET `malp`.`is_repricing` = 1
WHERE `temp`.`repricing_count` > 0;

SQL
        );

        //----------------------------------------

        $indexerListingProductVariationParentTable = $this->getConnection()->newTable(
            $this->getFullTableName('indexer_listing_product_variation_parent')
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
        $this->getConnection()->createTable($indexerListingProductVariationParentTable);
    }

    //########################################
}