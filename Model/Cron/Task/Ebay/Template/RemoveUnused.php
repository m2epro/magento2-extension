<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Template;

use Ess\M2ePro\Helper\Component\Ebay\Category as EbayCategory;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\Template\RemoveUnused
 */
class RemoveUnused extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'ebay/template/remove_unused';

    /**
     * @var int (in seconds)
     */
    protected $interval = 3600;

    const SAFE_CREATE_DATE_INTERVAL = 86400;

    //########################################

    protected function performActions()
    {
        $this->removeUnusedTemplates(\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION);
        $this->removeUnusedTemplates(\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT);
        $this->removeUnusedTemplates(\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION);
        $this->removeUnusedTemplates(\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT);
        $this->removeUnusedTemplates(\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING);
        $this->removeUnusedTemplates(\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY);

        $this->removeCategoriesTemplates();
        $this->removeStoreCategoriesTemplates();
    }

    //########################################

    protected function removeUnusedTemplates($templateNick)
    {
        $this->getOperationHistory()->addTimePoint(
            __METHOD__ . $templateNick,
            'Remove Unused "' . $templateNick . '" Policies'
        );

        /** @var \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager */
        $templateManager = $this->modelFactory->getObject('Ebay_Template_Manager')->setTemplate($templateNick);

        $connection = $this->resource->getConnection();

        $listingTable = $this->activeRecordFactory->getObject('Ebay\Listing')->getResource()->getMainTable();
        $listingProductTable = $this->activeRecordFactory->getObject('Ebay_Listing_Product')
            ->getResource()->getMainTable();

        $unionSelectListingTemplate = $connection->select()
            ->from($listingTable, ['result_field'=>$templateManager->getTemplateIdColumnName()])
            ->where($templateManager->getTemplateIdColumnName() . ' IS NOT NULL');
        $unionSelectListingProductTemplate = $connection->select()
            ->from($listingProductTable, ['result_field'=>$templateManager->getTemplateIdColumnName()])
            ->where($templateManager->getTemplateIdColumnName() . ' IS NOT NULL');

        $unionSelect = $connection->select()->union(
            [
                $unionSelectListingTemplate,
                $unionSelectListingProductTemplate,
            ]
        );

        $minCreateDate = $this->getHelper('Data')->getCurrentGmtDate(true) - self::SAFE_CREATE_DATE_INTERVAL;
        $minCreateDate = $this->getHelper('Data')->getDate($minCreateDate);

        $collection = $templateManager->getTemplateCollection();
        $collection->getSelect()->where('`id` NOT IN (' . $unionSelect->__toString() . ')');
        $collection->getSelect()->where('`is_custom_template` = 1');
        $collection->getSelect()->where('`create_date` < ?', $minCreateDate);

        $unusedTemplates = $collection->getItems();
        foreach ($unusedTemplates as $unusedTemplate) {
            $unusedTemplate->delete();
        }

        $this->getOperationHistory()->saveTimePoint(__METHOD__ . $templateNick);
    }

    // ---------------------------------------

    protected function removeCategoriesTemplates()
    {
        $this->getOperationHistory()->addTimePoint(__METHOD__, 'Remove Unused "Category" Policies');

        $connection = $this->resource->getConnection();

        $listingTable = $this->activeRecordFactory->getObject('Ebay\Listing')->getResource()->getMainTable();
        $listingProductTable = $this->activeRecordFactory->getObject('Ebay_Listing_Product')
            ->getResource()->getMainTable();
        $listingAutoCategoryGroupTable = $this->activeRecordFactory->getObject('Ebay_Listing_Auto_Category_Group')
            ->getResource()->getMainTable();

        $listingAutoGlobal = $connection->select()
            ->from(
                $listingTable,
                [
                    'result_field' => new \Zend_Db_Expr(
                        'IF (
                            auto_global_adding_template_category_id,
                            auto_global_adding_template_category_id,
                            auto_global_adding_template_category_secondary_id
                        )'
                    )
                ]
            )
            ->where('auto_global_adding_template_category_id IS NOT NULL')
            ->orWhere('auto_global_adding_template_category_secondary_id IS NOT NULL');

        $listingAutoWebsite = $connection->select()
            ->from(
                $listingTable,
                [
                    'result_field' => new \Zend_Db_Expr(
                        'IF (
                            auto_website_adding_template_category_id,
                            auto_website_adding_template_category_id,
                            auto_website_adding_template_category_secondary_id
                        )'
                    )
                ]
            )
            ->where('auto_website_adding_template_category_id IS NOT NULL')
            ->orWhere('auto_website_adding_template_category_secondary_id IS NOT NULL');

        $listingAutoCategory = $connection->select()
            ->from(
                $listingAutoCategoryGroupTable,
                [
                    'result_field' => new \Zend_Db_Expr(
                        'IF (
                            adding_template_category_id,
                            adding_template_category_id,
                            adding_template_category_secondary_id
                        )'
                    )
                ]
            )
            ->where('adding_template_category_id IS NOT NULL')
            ->orWhere('adding_template_category_secondary_id IS NOT NULL');

        $listingProduct = $connection->select()
            ->from(
                $listingProductTable,
                [
                    'result_field' => new \Zend_Db_Expr(
                        'IF (
                            template_category_id,
                            template_category_id,
                            template_category_secondary_id
                        )'
                    )
                ]
            )
            ->where('template_category_id IS NOT NULL')
            ->orWhere('template_category_secondary_id IS NOT NULL');

        $unionSelect = $connection->select()->union(
            [
                $listingAutoGlobal,
                $listingAutoWebsite,
                $listingAutoCategory,
                $listingProduct
            ]
        );

        $minCreateDate = $this->getHelper('Data')->getCurrentGmtDate(true) - self::SAFE_CREATE_DATE_INTERVAL;
        $minCreateDate = $this->getHelper('Data')->getDate($minCreateDate);

        $collection = $this->activeRecordFactory->getObject('Ebay_Template_Category')->getCollection();
        $collection->getSelect()->where('id NOT IN (' . $unionSelect->__toString() . ')');
        $collection->getSelect()->where('is_custom_template = 1');
        $collection->getSelect()->where('create_date < ?', $minCreateDate);

        $rememberTemplateIds = [];

        $listingCollection = $this->activeRecordFactory->getObject('Ebay_Listing')->getCollection();
        foreach ($listingCollection->getItems() as $eBayListing) {
            $additionalData = $eBayListing->getParentObject()->getSettings('additional_data');
            if (!isset($additionalData['mode_same_category_data'])) {
                continue;
            }

            $sameCategoryData = $additionalData['mode_same_category_data'];

            if (!empty($sameCategoryData[EbayCategory::TYPE_EBAY_MAIN])) {
                $rememberTemplateIds[] = $sameCategoryData[EbayCategory::TYPE_EBAY_MAIN]['template_id'];
            }

            if (!empty($sameCategoryData[EbayCategory::TYPE_EBAY_SECONDARY])) {
                $rememberTemplateIds[] = $sameCategoryData[EbayCategory::TYPE_EBAY_SECONDARY]['template_id'];
            }
        }

        if (!empty($rememberTemplateIds)) {
            $collection->getSelect()->where('id NOT IN (' . implode(',', $rememberTemplateIds) . ')');
        }

        $unusedTemplates = $collection->getItems();
        foreach ($unusedTemplates as $unusedTemplate) {
            /**@var \Ess\M2ePro\Model\Ebay\Template\Category $unusedTemplate */
            $unusedTemplate->delete();
        }

        $this->getOperationHistory()->saveTimePoint(__METHOD__);
    }

    protected function removeStoreCategoriesTemplates()
    {
        $this->getOperationHistory()->addTimePoint(__METHOD__, 'Remove Unused "Store Category" Policies');

        $connection = $this->resource->getConnection();

        $listingTable = $this->activeRecordFactory->getObject('Ebay\Listing')->getResource()->getMainTable();
        $listingProductTable = $this->activeRecordFactory->getObject('Ebay_Listing_Product')
            ->getResource()->getMainTable();
        $listingAutoCategoryGroupTable = $this->activeRecordFactory->getObject('Ebay_Listing_Auto_Category_Group')
            ->getResource()->getMainTable();

        $listingAutoGlobal = $connection->select()
            ->from(
                $listingTable,
                [
                    'result_field' => new \Zend_Db_Expr(
                        'IF (
                            auto_global_adding_template_store_category_id,
                            auto_global_adding_template_store_category_id,
                            auto_global_adding_template_store_category_secondary_id
                        )'
                    )
                ]
            )
            ->where('auto_global_adding_template_store_category_id IS NOT NULL')
            ->orWhere('auto_global_adding_template_store_category_secondary_id IS NOT NULL');

        $listingAutoWebsite = $connection->select()
            ->from(
                $listingTable,
                [
                    'result_field' => new \Zend_Db_Expr(
                        'IF (
                            auto_website_adding_template_store_category_id,
                            auto_website_adding_template_store_category_id,
                            auto_website_adding_template_store_category_secondary_id
                        )'
                    )
                ]
            )
            ->where('auto_website_adding_template_store_category_id IS NOT NULL')
            ->orWhere('auto_website_adding_template_store_category_secondary_id IS NOT NULL');

        $listingAutoCategory = $connection->select()
            ->from(
                $listingAutoCategoryGroupTable,
                [
                    'result_field' => new \Zend_Db_Expr(
                        'IF (
                            adding_template_store_category_id,
                            adding_template_store_category_id,
                            adding_template_store_category_secondary_id
                        )'
                    )
                ]
            )
            ->where('adding_template_store_category_id IS NOT NULL')
            ->orWhere('adding_template_store_category_secondary_id IS NOT NULL');

        $listingProduct = $connection->select()
            ->from(
                $listingProductTable,
                [
                    'result_field' => new \Zend_Db_Expr(
                        'IF (
                            template_store_category_id,
                            template_store_category_id,
                            template_store_category_secondary_id
                        )'
                    )
                ]
            )
            ->where('template_store_category_id IS NOT NULL')
            ->orWhere('template_store_category_secondary_id IS NOT NULL');

        $unionSelect = $connection->select()->union(
            [
                $listingAutoGlobal,
                $listingAutoWebsite,
                $listingAutoCategory,
                $listingProduct
            ]
        );

        $minCreateDate = $this->getHelper('Data')->getCurrentGmtDate(true) - self::SAFE_CREATE_DATE_INTERVAL;
        $minCreateDate = $this->getHelper('Data')->getDate($minCreateDate);

        $collection = $this->activeRecordFactory->getObject('Ebay_Template_StoreCategory')->getCollection();
        $collection->getSelect()->where('`id` NOT IN (' . $unionSelect->__toString() . ')');
        $collection->getSelect()->where('`create_date` < ?', $minCreateDate);

        $rememberTemplateIds = [];

        $listingCollection = $this->activeRecordFactory->getObject('Ebay_Listing')->getCollection();
        foreach ($listingCollection->getItems() as $eBayListing) {
            $additionalData = $eBayListing->getParentObject()->getSettings('additional_data');
            if (!isset($additionalData['mode_same_category_data'])) {
                continue;
            }

            $sameCategoryData = $additionalData['mode_same_category_data'];

            if (!empty($sameCategoryData[EbayCategory::TYPE_STORE_MAIN])) {
                $rememberTemplateIds[] = $sameCategoryData[EbayCategory::TYPE_STORE_MAIN]['template_id'];
            }

            if (!empty($sameCategoryData[EbayCategory::TYPE_STORE_SECONDARY])) {
                $rememberTemplateIds[] = $sameCategoryData[EbayCategory::TYPE_STORE_SECONDARY]['template_id'];
            }
        }

        if (!empty($rememberTemplateIds)) {
            $collection->getSelect()->where('id NOT IN (' . implode(',', $rememberTemplateIds) . ')');
        }

        $unusedTemplates = $collection->getItems();
        foreach ($unusedTemplates as $unusedTemplate) {
            /**@var \Ess\M2ePro\Model\Ebay\Template\StoreCategory $unusedTemplate */
            $unusedTemplate->delete();
        }

        $this->getOperationHistory()->saveTimePoint(__METHOD__);
    }

    //########################################
}
