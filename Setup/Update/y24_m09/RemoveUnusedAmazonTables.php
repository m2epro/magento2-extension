<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m09;

class RemoveUnusedAmazonTables extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->getConnection()
             ->dropTable($this->getFullTableName('amazon_dictionary_specific'));

        $this->getConnection()
             ->dropTable($this->getFullTableName('amazon_dictionary_category'));

        $this->getConnection()
             ->dropTable($this->getFullTableName('amazon_dictionary_category_product_data'));

        // ----------------------------------------

        $registryTable = $this->getFullTableName(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_REGISTRY);

        $this->getConnection()->delete(
            $registryTable,
            [
                '`key` = ?' => '/amazon/category/recent/',
            ]
        );
    }
}
