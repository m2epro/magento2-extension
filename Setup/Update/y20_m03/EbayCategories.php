<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */
// @codingStandardsIgnoreFile

namespace Ess\M2ePro\Setup\Update\y20_m03;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m03\EbayCategories
 */
class EbayCategories extends AbstractFeature
{

    public function getBackupTables()
    {
        return [
            'ebay_template_category',
            'ebay_template_other_category'
        ];
    }

    public function execute()
    {
        $moduleConfig = $this->getConfigModifier('module');
        $moduleConfig->delete('/view/ebay/template/category/', 'use_last_specifics');

        $stmt = $this->getConnection()
            ->select()
            ->from($this->getFullTableName('listing'))
            ->where('component_mode = ?', 'ebay')
            ->query();

        while ($row = $stmt->fetch()) {
            $additionalData = (array)json_decode($row['additional_data'], true);
            unset($additionalData['mode_same_category_data']);
            unset($additionalData['ebay_primary_category']);
            unset($additionalData['ebay_store_primary_category']);

            $this->getConnection()->update(
                    $this->getFullTableName('listing'),
                    ['additional_data' => json_encode($additionalData)],
                    ['id = ?' => $row['id']]
                );
        }
        //----------------------------------------

        $this->createColumns();

        $this->processCategoryTemplates();
        $this->processOtherCategoryTemplates();

        $this->removeColumns();
    }

    //########################################

    private function createColumns()
    {
        $this->getTableModifier('ebay_template_category')
            ->addColumn(
                'is_custom_template',
                'SMALLINT(5) UNSIGNED NOT NULL',
                '0',
                'marketplace_id',
                true
            );

        //----------------------------------------

        $this->renameTable(
            'ebay_template_other_category',
            'ebay_template_store_category'
        );

        $this->getTableModifier('ebay_template_store_category')
            ->addColumn(
                'category_id',
                'INT(10) UNSIGNED NOT NULL',
                null,
                'account_id',
                false,
                false
            )
            ->addColumn(
                'category_path',
                'VARCHAR(255)',
                'NULL',
                'category_id',
                false,
                false
            )
            ->addColumn(
                'category_mode',
                'SMALLINT(5) UNSIGNED NOT NULL',
                2,
                'category_path',
                false,
                false
            )
            ->addColumn(
                'category_attribute',
                'VARCHAR(255) NOT NULL',
                null,
                'category_mode',
                false,
                false
            )
            ->commit();

        //----------------------------------------

        $this->getTableModifier('ebay_listing_product')
            ->addColumn(
                'template_category_secondary_id',
                'INT(11) UNSIGNED',
                'NULL',
                'template_category_id',
                true,
                false
            )
            ->addColumn(
                'template_store_category_id',
                'INT(11) UNSIGNED',
                'NULL',
                'template_category_secondary_id',
                true,
                false
            )
            ->addColumn(
                'template_store_category_secondary_id',
                'INT(11) UNSIGNED',
                'NULL',
                'template_store_category_id',
                true,
                false
            )
            ->commit();

        $this->getTableModifier('ebay_listing')
            ->addColumn(
                'auto_global_adding_template_category_secondary_id',
                'INT(11) UNSIGNED',
                'NULL',
                'auto_global_adding_template_category_id',
                true,
                false
            )
            ->addColumn(
                'auto_global_adding_template_store_category_id',
                'INT(11) UNSIGNED',
                'NULL',
                'auto_global_adding_template_category_secondary_id',
                true,
                false
            )
            ->addColumn(
                'auto_global_adding_template_store_category_secondary_id',
                'INT(11) UNSIGNED',
                'NULL',
                'auto_global_adding_template_store_category_id',
                true,
                false
            )
            ->addColumn(
                'auto_website_adding_template_category_secondary_id',
                'INT(11) UNSIGNED',
                'NULL',
                'auto_website_adding_template_category_id',
                true,
                false
            )
            ->addColumn(
                'auto_website_adding_template_store_category_id',
                'INT(11) UNSIGNED',
                'NULL',
                'auto_website_adding_template_category_secondary_id',
                true,
                false
            )
            ->addColumn(
                'auto_website_adding_template_store_category_secondary_id',
                'INT(11) UNSIGNED',
                'NULL',
                'auto_website_adding_template_store_category_id',
                true,
                false
            )
            ->commit();

        $this->getTableModifier('ebay_listing_auto_category_group')
            ->addColumn(
                'adding_template_category_secondary_id',
                'INT(11) UNSIGNED',
                'NULL',
                'adding_template_category_id',
                true,
                false
            )
            ->addColumn(
                'adding_template_store_category_id',
                'INT(11) UNSIGNED',
                'NULL',
                'adding_template_category_secondary_id',
                true,
                false
            )
            ->addColumn(
                'adding_template_store_category_secondary_id',
                'INT(11) UNSIGNED',
                'NULL',
                'adding_template_store_category_id',
                true,
                false
            )
            ->commit();
    }

    private function removeColumns()
    {
        $this->getTableModifier('ebay_listing_product')
            ->dropColumn('template_other_category_id');

        $this->getTableModifier('ebay_listing')
            ->dropColumn('auto_global_adding_template_other_category_id', true, false)
            ->dropColumn('auto_website_adding_template_other_category_id', true, false)
            ->commit();

        $this->getTableModifier('ebay_listing_auto_category_group')
            ->dropColumn('adding_template_other_category_id');
    }

    //########################################

    private function processCategoryTemplates()
    {
        $modifier = $this->getTableModifier('ebay_template_category');
        if (!$modifier->isColumnExists('category_main_attribute')) {
            return;
        }

        $this->getConnection()->update(
            $this->getFullTableName('ebay_template_category'),
            ['is_custom_template' => 1]
        );

        $stmt = $this->getConnection()
            ->select()
            ->from(
                ['metc' => $this->getFullTableName('ebay_template_category')]
            )
            ->joinInner(
                ['melp' => $this->getFullTableName('ebay_listing_product')],
                'melp.template_category_id=metc.id'
            )
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(
                [
                    'template_id'    => 'metc.id',
                    'template_value' => new \Zend_Db_Expr(
                        'IF(metc.category_main_mode = 1, metc.category_main_id, metc.category_main_attribute)'
                    ),
                    'usages' => new \Zend_Db_Expr('COUNT(melp.listing_product_id)')
                ]
            )
            ->group(['metc.id'])
            ->query();

        $mostUsed = [];
        while ($row = $stmt->fetch()) {
            if (!isset($mostUsed[$row['template_value']]) ||
                $row['usages'] > $mostUsed[$row['template_value']]['usages']
            ) {
                $mostUsed[$row['template_value']] = $row;
            }
        }

        foreach ($mostUsed as $categoryValue => $data) {
            $this->getConnection()->update(
                $this->getFullTableName('ebay_template_category'),
                ['is_custom_template' => 0],
                ['id = ?' => $data['template_id']]
            );
        }

        $this->getTableModifier('ebay_template_category')
            ->renameColumn('category_main_id', 'category_id', true, false)
            ->renameColumn('category_main_path', 'category_path', true, false)
            ->renameColumn('category_main_mode', 'category_mode', true, false)
            ->renameColumn('category_main_attribute', 'category_attribute', true, false)
            ->commit();
    }

    private function processOtherCategoryTemplates()
    {
        $this->processCategorySecondaryTemplates();
        $this->processStoreCategoryTemplates();
        $this->processStoreCategorySecondaryTemplates();

        $this->getConnection()->delete(
            $this->getFullTableName('ebay_template_store_category'),
            "category_id = 0 AND category_attribute = ''"
        );
    }

    private function processCategorySecondaryTemplates()
    {
        $modifier = $this->getTableModifier('ebay_template_store_category');
        if (!$modifier->isColumnExists('category_secondary_mode')) {
            return;
        }

        $stmt = $this->getConnection()
            ->select()
            ->from(
                ['metsc' => $this->getFullTableName('ebay_template_store_category')]
            )
            ->where('category_secondary_mode != 0')
            ->query();

        while ($row = $stmt->fetch()) {

            $this->getConnection()->insert(
                $this->getFullTableName('ebay_template_category'),
                [
                    'is_custom_template' => '1',
                    'marketplace_id'     => $row['marketplace_id'],
                    'category_id'        => $row['category_secondary_id'],
                    'category_path'      => $row['category_secondary_path'],
                    'category_mode'      => $row['category_secondary_mode'],
                    'category_attribute' => $row['category_secondary_attribute'],
                    'create_date'        => $row['create_date'],
                    'update_date'        => $row['update_date']
                ]
            );
            $newId = $this->getConnection()->lastInsertId();

            $this->getConnection()->update(
                $this->getFullTableName('ebay_listing_product'),
                ['template_category_secondary_id' => $newId],
                ['template_other_category_id = ?' => $row['id']]
            );

            $this->getConnection()->update(
                $this->getFullTableName('ebay_listing'),
                ['auto_global_adding_template_category_secondary_id' => $newId],
                ['auto_global_adding_template_other_category_id = ?' => $row['id']]
            );

            $this->getConnection()->update(
                $this->getFullTableName('ebay_listing'),
                ['auto_website_adding_template_category_secondary_id' => $newId],
                ['auto_website_adding_template_other_category_id = ?' => $row['id']]
            );

            $this->getConnection()->update(
                $this->getFullTableName('ebay_listing_auto_category_group'),
                ['adding_template_category_secondary_id' => $newId],
                ['adding_template_other_category_id = ?' => $row['id']]
            );
        }

        $this->getTableModifier('ebay_template_store_category')
            ->dropColumn('marketplace_id', true, false)
            ->dropColumn('category_secondary_id', true, false)
            ->dropColumn('category_secondary_path', true, false)
            ->dropColumn('category_secondary_mode', true, false)
            ->dropColumn('category_secondary_attribute', true, false)
            ->commit();
    }

    private function processStoreCategoryTemplates()
    {
        $modifier = $this->getTableModifier('ebay_template_store_category');
        if (!$modifier->isColumnExists('store_category_main_mode')) {
            return;
        }

        $stmt = $this->getConnection()
            ->select()
            ->from($this->getFullTableName('ebay_template_store_category'))
            ->where('store_category_main_mode != 0')
            ->query();

        while ($row = $stmt->fetch()) {

            $this->getConnection()->insert(
                $this->getFullTableName('ebay_template_store_category'),
                [
                    'account_id'         => $row['account_id'],
                    'category_id'        => $row['store_category_main_id'],
                    'category_path'      => $row['store_category_main_path'],
                    'category_mode'      => $row['store_category_main_mode'],
                    'category_attribute' => $row['store_category_main_attribute'],
                    'create_date'        => $row['create_date'],
                    'update_date'        => $row['update_date']
                ]
            );
            $newId = $this->getConnection()->lastInsertId();

            $this->getConnection()->update(
                $this->getFullTableName('ebay_listing_product'),
                ['template_store_category_id' => $newId],
                ['template_other_category_id = ?' => $row['id']]
            );

            $this->getConnection()->update(
                $this->getFullTableName('ebay_listing'),
                ['auto_global_adding_template_store_category_id' => $newId],
                ['auto_global_adding_template_other_category_id = ?' => $row['id']]
            );

            $this->getConnection()->update(
                $this->getFullTableName('ebay_listing'),
                ['auto_website_adding_template_store_category_id' => $newId],
                ['auto_website_adding_template_other_category_id = ?' => $row['id']]
            );

            $this->getConnection()->update(
                $this->getFullTableName('ebay_listing_auto_category_group'),
                ['adding_template_store_category_id' => $newId],
                ['adding_template_other_category_id = ?' => $row['id']]
            );
        }

        $this->getTableModifier('ebay_template_store_category')
            ->dropColumn('store_category_main_id', true, false)
            ->dropColumn('store_category_main_path', true, false)
            ->dropColumn('store_category_main_mode', true, false)
            ->dropColumn('store_category_main_attribute', true, false)
            ->commit();
    }

    private function processStoreCategorySecondaryTemplates()
    {
        $modifier = $this->getTableModifier('ebay_template_store_category');
        if (!$modifier->isColumnExists('store_category_secondary_mode')) {
            return;
        }

        $stmt = $this->getConnection()
            ->select()
            ->from($this->getFullTableName('ebay_template_store_category'))
            ->where('store_category_secondary_mode != 0')
            ->query();

        while ($row = $stmt->fetch()) {

            $this->getConnection()->insert(
                $this->getFullTableName('ebay_template_store_category'),
                [
                    'account_id'         => $row['account_id'],
                    'category_id'        => $row['store_category_secondary_id'],
                    'category_path'      => $row['store_category_secondary_path'],
                    'category_mode'      => $row['store_category_secondary_mode'],
                    'category_attribute' => $row['store_category_secondary_attribute'],
                    'create_date'        => $row['create_date'],
                    'update_date'        => $row['update_date']
                ]
            );
            $newId = $this->getConnection()->lastInsertId();

            $this->getConnection()->update(
                $this->getFullTableName('ebay_listing_product'),
                ['template_store_category_secondary_id' => $newId],
                ['template_other_category_id = ?' => $row['id']]
            );

            $this->getConnection()->update(
                $this->getFullTableName('ebay_listing'),
                ['auto_global_adding_template_store_category_secondary_id' => $newId],
                ['auto_global_adding_template_other_category_id = ?' => $row['id']]
            );

            $this->getConnection()->update(
                $this->getFullTableName('ebay_listing'),
                ['auto_website_adding_template_store_category_secondary_id' => $newId],
                ['auto_website_adding_template_other_category_id = ?' => $row['id']]
            );

            $this->getConnection()->update(
                $this->getFullTableName('ebay_listing_auto_category_group'),
                ['adding_template_store_category_secondary_id' => $newId],
                ['adding_template_other_category_id = ?' => $row['id']]
            );
        }

        $this->getTableModifier('ebay_template_store_category')
            ->dropColumn('store_category_secondary_id', true, false)
            ->dropColumn('store_category_secondary_path', true, false)
            ->dropColumn('store_category_secondary_mode', true, false)
            ->dropColumn('store_category_secondary_attribute', true, false)
            ->commit();
    }

    //########################################
}
