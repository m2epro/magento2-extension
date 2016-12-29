<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_2_0__v1_3_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Magento\Framework\DB\Ddl\Table;

class AmazonShippingTemplate extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['amazon_listing_product', 'amazon_account', 'amazon_template_synchronization', 'listing_product'];
    }

    public function execute()
    {
        $this->getTableModifier('amazon_listing_product')
            ->addColumn('template_shipping_template_id', 'INT(10) UNSIGNED', 'NULL', 'template_description_id', true);

        $this->getTableModifier('amazon_account')
            ->addColumn('shipping_mode', 'INT(10) UNSIGNED', '1', 'related_store_id');

        $this->getTableModifier('amazon_template_synchronization')
            ->renameColumn('revise_change_shipping_override_template', 'revise_change_shipping_template');

        //----------------------------------------

        $tempTable = $this->getFullTableName('listing_product');
        $queryStmt = $this->getConnection()->query(<<<SQL
SELECT `id`,
       `synch_reasons`
FROM `{$tempTable}`
WHERE `synch_reasons` LIKE '%shippingOverrideTemplate%';
SQL
        );

        while ($row = $queryStmt->fetch()) {

            $reasons = explode(',', $row['synch_reasons']);
            $reasons = array_unique(array_filter($reasons));

            array_walk($reasons, function (&$el){
                $el = str_replace('shippingOverrideTemplate', 'shippingTemplate', $el);
            });
            $reasons = implode(',', $reasons);

            $this->getConnection()->query(<<<SQL
UPDATE `{$tempTable}`
SET `synch_reasons` = '{$reasons}'
WHERE `id` = {$row['id']}
SQL
            );
        }

        //----------------------------------------

        $amazonTemplateShippingTemplateTable = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_template_shipping_template')
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
                'template_name', Table::TYPE_TEXT, 255,
                ['nullable' => false]
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
            ->addIndex('template_name', 'template_name')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonTemplateShippingTemplateTable);
    }

    //########################################
}