<?php

namespace Ess\M2ePro\Setup\Update\y23_m03;

use Magento\Framework\DB\Ddl\Table;

class UpgradeTags extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Adapter_Exception
     */
    public function execute()
    {
        if (!$this->getTableModifier('tag')->isColumnExists('nick')) {
            return;
        }

        //----------------------------------------

        $this->getConnection()->update(
            $this->getFullTableName('tag'),
            ['error_code' => 'has_error'],
            ['nick = ?' => 'has_error']
        );
        $this->dropDefault('tag', 'error_code');
        $this->modifyNotNull('tag', 'error_code', 'VARCHAR(100)');
        $this->addUniqueKeyForTagErrorCode();

        //----------------------------------------

        $this->getTableModifier('tag')->addColumn('text', 'VARCHAR(255)', null, 'error_code');
        $this->getConnection()->update(
            $this->getFullTableName('tag'),
            ['text' => 'Has error'],
            ['nick = ?' => 'has_error']
        );
        $this->getConnection()->update(
            $this->getFullTableName('tag'),
            ['text' => 'Required Item Specifics are missing'],
            ['nick = ?' => 'missing_item_specific']
        );
        $this->dropDefault('tag', 'text');
        $this->modifyNotNull('tag', 'text', 'VARCHAR(255)');

        //----------------------------------------

        $this->getTableModifier('tag')->dropColumn('nick');

        //----------------------------------------

        $createDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $createDate = $createDate->format('Y-m-d H:i:s');
        $this->addCreatedDateColumn('tag', $createDate);
        $this->addCreatedDateColumn('listing_product_tag_relation', $createDate);
    }

    /**
     * @param string $tableName
     * @param string $column
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Adapter_Exception
     */
    private function dropDefault(string $tableName, string $column): void
    {
        $tableName = $this->getFullTableName($tableName);
        $query = sprintf('ALTER TABLE %s ALTER %s DROP DEFAULT', $tableName, $column);
        $this->getConnection()->query($query);
    }

    /**
     * @param string $tableName
     * @param string $columnName
     * @param string $type
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Adapter_Exception
     */
    private function modifyNotNull(string $tableName, string $columnName, string $type): void
    {
        $tableName = $this->getFullTableName($tableName);
        $query = sprintf('ALTER TABLE %s MODIFY %s %s NOT NULL', $tableName, $columnName, $type);
        $this->getConnection()->query($query);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Adapter_Exception
     */
    private function addUniqueKeyForTagErrorCode(): void
    {
        $tableName = $this->getFullTableName('tag');
        $query = sprintf('ALTER TABLE %1$s ADD UNIQUE `%2$s` (`%2$s`)', $tableName, 'error_code');
        $this->getConnection()->query($query);
    }

    /**
     * @param string $tableName
     * @param string $createdDate
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Adapter_Exception
     */
    private function addCreatedDateColumn(string $tableName, string $createdDate): void
    {
        $modifier = $this->getTableModifier($tableName);
        $modifier->addColumn('create_date', Table::TYPE_DATETIME, 'NULL', null, false, false);
        $modifier->commit();

        $this->getConnection()->update(
            $this->getFullTableName($tableName),
            ['create_date' => $createdDate]
        );

        $this->dropDefault($tableName, 'create_date');
        $this->modifyNotNull($tableName, 'create_date', Table::TYPE_DATETIME);
    }
}
