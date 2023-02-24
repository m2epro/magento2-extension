<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y23_m02;

use Magento\Framework\DB\Ddl\Table;

class AddTags extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    private const TAG_TABLE_NAME = 'tag';
    private const TAG_RELATION_TABLE_NAME = 'listing_product_tag_relation';

    /**
     * @return void
     * @throws \Zend_Db_Exception
     */
    public function execute()
    {
        if ($this->notExistsTable(self::TAG_TABLE_NAME)) {
            $this->createTagTable();
            $this->insertDataIntoTagTable();
        }

        if ($this->notExistsTable(self::TAG_RELATION_TABLE_NAME)) {
            $this->createListingProductTagRelation();
        }
    }

    /**
     * @param string $tableName
     *
     * @return bool
     */
    private function notExistsTable(string $tableName): bool
    {
        return !$this->installer->tableExists($this->getFullTableName($tableName));
    }

    /**
     * @return void
     * @throws \Zend_Db_Exception
     */
    private function createTagTable(): void
    {
        $table = $this->getConnection()->newTable($this->getFullTableName(self::TAG_TABLE_NAME));

        $table->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            [
                'unsigned' => true,
                'primary' => true,
                'nullable' => false,
                'auto_increment' => true,
            ]
        );
        $table->addColumn(
            'nick',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false]
        );

        $table->setOption('type', 'INNODB');
        $table->setOption('charset', 'utf8');
        $table->setOption('collate', 'utf8_general_ci');
        $table->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($table);
    }

    /**
     * @return void
     * @throws \Zend_Db_Exception
     */
    private function insertDataIntoTagTable(): void
    {
        $this->getConnection()->insertMultiple(
            $this->getFullTableName(self::TAG_TABLE_NAME),
            [
                ['nick' => 'has_error'],
                ['nick' => 'missing_item_specific'],
            ]
        );
    }

    /**
     * @return void
     * @throws \Zend_Db_Exception
     */
    private function createListingProductTagRelation(): void
    {
        $table = $this->getConnection()->newTable($this->getFullTableName(self::TAG_RELATION_TABLE_NAME));

        $table->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            [
                'unsigned' => true,
                'primary' => true,
                'nullable' => false,
                'auto_increment' => true,
            ]
        );
        $table->addColumn(
            'listing_product_id',
            Table::TYPE_INTEGER,
            null,
            [
                'unsigned' => true,
                'nullable' => false,
            ]
        );
        $table->addColumn(
            'tag_id',
            Table::TYPE_INTEGER,
            null,
            [
                'unsigned' => true,
                'nullable' => false,
            ]
        );

        $table->addIndex('listing_product_id', 'listing_product_id');
        $table->addIndex('tag_id', 'tag_id');

        $table->setOption('type', 'INNODB');
        $table->setOption('charset', 'utf8');
        $table->setOption('collate', 'utf8_general_ci');
        $table->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($table);
    }
}
