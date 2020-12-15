<?php

namespace Ess\M2ePro\Setup\Update\y20_m10;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m10\SellOnAnotherSite
 */
class SellOnAnotherSite extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->changeRowFormat();

        $this->getTableModifier('amazon_listing')->addColumn(
            'product_add_ids',
            'TEXT',
            'NULL',
            'restock_date_custom_attribute'
        );
    }

    private function changeRowFormat()
    {
        $dbName = $this->helperFactory->getObject('Magento')->getDatabaseName();
        $tableName = $this->getFullTableName('amazon_listing');
        $sql = <<<SQL
SELECT `row_format`
FROM `information_schema`.`tables`
WHERE `TABLE_SCHEMA` = '{$dbName}' and `TABLE_NAME` = '{$tableName}'
SQL;

        $result = array_change_key_case($this->getConnection()->query($sql)->fetch());

        if (strtolower($result['row_format']) != 'dynamic') {
            $this->installer->run(
                <<<SQL
ALTER TABLE `{$tableName}` ROW_FORMAT=DYNAMIC
SQL
            );
        }
    }

    //########################################
}
