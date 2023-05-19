<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y23_m01;

use Magento\Framework\DB\Ddl\Table;

class AmazonProductTypes extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    private const LONG_COLUMN_SIZE = 16777217;
    private const WIZARD_STATUS_ACTIVE = 1;
    private const WIZARD_STATUS_SKIPPED = 3;

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     * @throws \Zend_Db_Exception
     */
    public function execute(): void
    {
        if (!$this->installer->tableExists($this->getFullTableName('amazon_dictionary_product_type'))) {
            $this->createTableAmazonDictionaryProductType();
        }

        if (!$this->installer->tableExists($this->getFullTableName('amazon_template_product_type'))) {
            $this->createTableAmazonTemplateProductType();
        }

        $tableModifier = $this->getTableModifier('amazon_listing_product');
        if (!$tableModifier->isColumnExists('template_product_type_id')) {
            $tableModifier
                 ->addColumn(
                     'template_product_type_id',
                     'INT UNSIGNED',
                     'NULL',
                     'listing_product_id',
                     true,
                     false
                 )
                 ->commit();
        }

        $tableModifier = $this->getTableModifier('amazon_dictionary_marketplace');
        if (!$tableModifier->isColumnExists('product_types')) {
            $tableModifier
                 ->addColumn(
                     'product_types',
                     'LONGTEXT',
                     'NULL',
                     'server_details_last_update_date',
                     false,
                     false
                 )
                 ->commit();
        }

        if ($tableModifier->isColumnExists('product_data')) {
            $tableModifier
                 ->dropColumn('product_data')
                 ->commit();
        }

        $this->addWizard();
        $this->addAutoActionFields();
        $this->enableAsinCreationForAllMarketplaces();
        $this->removeImagesTagFromScheduledActions();
    }

    /**
     * @return void
     * @throws \Zend_Db_Exception
     */
    private function createTableAmazonDictionaryProductType(): void
    {
        $dictionaryProductTypeTable = $this->getConnection()
            ->newTable($this->getFullTableName('amazon_dictionary_product_type'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'primary' => true,
                    'nullable' => false,
                    'auto_increment' => true,
                ]
            )
            ->addColumn(
                'marketplace_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'nick',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'title',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                'scheme',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addColumn(
                'invalid',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addIndex(
                'marketplace_id_nick',
                ['marketplace_id', 'nick'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($dictionaryProductTypeTable);
    }

    /**
     * @return void
     * @throws \Zend_Db_Exception
     */
    private function createTableAmazonTemplateProductType(): void
    {
        $templateProductTypeTable = $this->getConnection()
            ->newTable($this->getFullTableName('amazon_template_product_type'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'dictionary_product_type_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'settings',
                Table::TYPE_TEXT,
                self::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addColumn(
                'update_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'create_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addIndex(
                'dictionary_product_type_id',
                ['dictionary_product_type_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');
        $this->getConnection()->createTable($templateProductTypeTable);
    }

    /**
     * @return void
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    private function addWizard(): void
    {
        $query = $this->getConnection()
            ->select()
            ->from($this->getFullTableName('wizard'))
            ->where('nick = ?', 'amazonMigrationToProductTypes')
            ->query();

        $row = $query->fetch();
        if ($row) {
            return;
        }

        $query = $this->getConnection()
            ->select()
            ->from($this->getFullTableName('marketplace'))
            ->where('component_mode = "amazon" AND status = 1')
            ->query();

        $row = $query->fetch();
        $status = $row ? self::WIZARD_STATUS_ACTIVE : self::WIZARD_STATUS_SKIPPED;

        $this->getConnection()->insert(
            $this->getFullTableName('wizard'),
            [
                'nick' => 'amazonMigrationToProductTypes',
                'view' => 'amazon',
                'status' => $status,
                'step' => null,
                'type' => 1,
                'priority' => 6,
            ]
        );
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    private function addAutoActionFields(): void
    {
        $tableModifier = $this->getTableModifier('amazon_listing');
        if (!$tableModifier->isColumnExists('auto_global_adding_product_type_template_id')) {
            $tableModifier
                 ->addColumn(
                     'auto_global_adding_product_type_template_id',
                     'INT UNSIGNED',
                     'NULL',
                     'auto_global_adding_description_template_id',
                     true,
                     false
                 )
                 ->commit();
        }

        if (!$tableModifier->isColumnExists('auto_website_adding_product_type_template_id')) {
            $tableModifier
                 ->addColumn(
                     'auto_website_adding_product_type_template_id',
                     'INT UNSIGNED',
                     'NULL',
                     'auto_website_adding_description_template_id',
                     true,
                     false
                 )
                 ->commit();
        }

        $tableModifier = $this->getTableModifier('amazon_listing_auto_category_group');
        if (!$tableModifier->isColumnExists('adding_product_type_template_id')) {
            $tableModifier
                 ->addColumn(
                     'adding_product_type_template_id',
                     'INT UNSIGNED',
                     'NULL',
                     'adding_description_template_id',
                     true,
                     false
                 )
                 ->commit();
        }
    }

    /**
     * @return void
     * @throws \Zend_Db_Adapter_Exception
     */
    private function enableAsinCreationForAllMarketplaces(): void
    {
        $this->getConnection()->update(
            $this->getFullTableName('amazon_marketplace'),
            ['is_new_asin_available' => 1],
            'TRUE'
        );
    }

    /**
     * @return void
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    private function removeImagesTagFromScheduledActions(): void
    {
        $scheduledAction = $this->getFullTableName('listing_product_scheduled_action');

        $stmt = $this->getConnection()->select()
            ->from(
                $scheduledAction,
                ['id', 'tag']
            )
            ->where('component = ?', 'amazon')
            ->where('tag LIKE ?', '%images%')
            ->query();

        while ($row = $stmt->fetch()) {
            $tags = array_filter(
                explode('/', $row['tag']),
                function ($tag) {
                    return !empty($tag) && $tag !== 'images';
                }
            );

            $tags = '/' . implode('/', $tags) . '/';
            $this->getConnection()->update(
                $scheduledAction,
                ['tag' => $tags],
                ['id = ?' => (int)$row['id']]
            );
        }
    }
}
