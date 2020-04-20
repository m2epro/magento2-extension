<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Template;

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
        $this->removeOtherCategoriesTemplates();
    }

    //########################################

    protected function removeUnusedTemplates($templateNick)
    {
        $this->getOperationHistory()->addTimePoint(
            __METHOD__.$templateNick,
            'Remove Unused "'.$templateNick.'" Policies'
        );

        /** @var \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager */
        $templateManager = $this->modelFactory->getObject('Ebay_Template_Manager')->setTemplate($templateNick);

        $connection = $this->resource->getConnection();

        $listingTable = $this->activeRecordFactory->getObject('Ebay\Listing')->getResource()->getMainTable();
        $listingProductTable = $this->activeRecordFactory->getObject('Ebay_Listing_Product')
            ->getResource()->getMainTable();

        $unionSelectListingTemplate = $connection->select()
            ->from($listingTable, ['result_field'=>$templateManager->getTemplateIdColumnName()])
            ->where($templateManager->getTemplateIdColumnName().' IS NOT NULL');
        $unionSelectListingCustom = $connection->select()
            ->from($listingTable, ['result_field'=>$templateManager->getCustomIdColumnName()])
            ->where($templateManager->getCustomIdColumnName().' IS NOT NULL');
        $unionSelectListingProductTemplate = $connection->select()
            ->from($listingProductTable, ['result_field'=>$templateManager->getTemplateIdColumnName()])
            ->where($templateManager->getTemplateIdColumnName().' IS NOT NULL');
        $unionSelectListingProductCustom = $connection->select()
            ->from($listingProductTable, ['result_field'=>$templateManager->getCustomIdColumnName()])
            ->where($templateManager->getCustomIdColumnName().' IS NOT NULL');

        $unionSelect = $connection->select()->union(
            [
                $unionSelectListingTemplate,
                $unionSelectListingCustom,
                $unionSelectListingProductTemplate,
                $unionSelectListingProductCustom
            ]
        );

        $minCreateDate = $this->getHelper('Data')->getCurrentGmtDate(true) - self::SAFE_CREATE_DATE_INTERVAL;
        $minCreateDate = $this->getHelper('Data')->getDate($minCreateDate);

        $collection = $templateManager->getTemplateCollection();
        $collection->getSelect()->where('`id` NOT IN ('.$unionSelect->__toString().')');
        $collection->getSelect()->where('`is_custom_template` = 1');
        $collection->getSelect()->where('`create_date` < ?', $minCreateDate);

        $unusedTemplates = $collection->getItems();
        foreach ($unusedTemplates as $unusedTemplate) {
            $unusedTemplate->delete();
        }

        $this->getOperationHistory()->saveTimePoint(__METHOD__.$templateNick);
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

        $minCreateDate = $this->getHelper('Data')->getCurrentGmtDate(true) - self::SAFE_CREATE_DATE_INTERVAL;
        $minCreateDate = $this->getHelper('Data')->getDate($minCreateDate);

        $unionListingAutoGlobalSelect = $connection->select()
            ->from($listingTable, ['result_field'=>'auto_global_adding_template_category_id'])
            ->where('auto_global_adding_template_category_id IS NOT NULL');
        $unionListingAutoWebsiteSelect = $connection->select()
            ->from($listingTable, ['result_field'=>'auto_website_adding_template_category_id'])
            ->where('auto_website_adding_template_category_id IS NOT NULL');
        $unionListingAutoCategorySelect = $connection->select()
            ->from($listingAutoCategoryGroupTable, ['result_field'=>'adding_template_category_id'])
            ->where('adding_template_category_id IS NOT NULL');
        $unionSelectListingProductTemplate = $connection->select()
            ->from($listingProductTable, ['result_field'=>'template_category_id'])
            ->where('template_category_id IS NOT NULL');

        $unionSelect = $connection->select()->union(
            [
                $unionListingAutoGlobalSelect,
                $unionListingAutoWebsiteSelect,
                $unionListingAutoCategorySelect,
                $unionSelectListingProductTemplate
            ]
        );

        $collection = $this->activeRecordFactory->getObject('Ebay_Template_Category')->getCollection();
        $collection->getSelect()->where('`id` NOT IN ('.$unionSelect->__toString().')');
        $collection->getSelect()->where('`create_date` < ?', $minCreateDate);

        $unusedTemplates = $collection->getItems();
        foreach ($unusedTemplates as $unusedTemplate) {
            $unusedTemplate->delete();
        }

        $this->getOperationHistory()->saveTimePoint(__METHOD__);
    }

    protected function removeOtherCategoriesTemplates()
    {
        $this->getOperationHistory()->addTimePoint(__METHOD__, 'Remove Unused "Other Category" Policies');

        $connection = $this->resource->getConnection();

        $listingTable = $this->activeRecordFactory->getObject('Ebay\Listing')->getResource()->getMainTable();
        $listingProductTable = $this->activeRecordFactory->getObject('Ebay_Listing_Product')
            ->getResource()->getMainTable();
        $listingAutoCategoryGroupTable = $this->activeRecordFactory->getObject('Ebay_Listing_Auto_Category_Group')
            ->getResource()->getMainTable();

        $minCreateDate = $this->getHelper('Data')->getCurrentGmtDate(true) - self::SAFE_CREATE_DATE_INTERVAL;
        $minCreateDate = $this->getHelper('Data')->getDate($minCreateDate);

        $unionListingAutoGlobalSelect = $connection->select()
            ->from($listingTable, ['result_field'=>'auto_global_adding_template_other_category_id'])
            ->where('auto_global_adding_template_other_category_id IS NOT NULL');
        $unionListingAutoWebsiteSelect = $connection->select()
            ->from($listingTable, ['result_field'=>'auto_website_adding_template_other_category_id'])
            ->where('auto_website_adding_template_other_category_id IS NOT NULL');
        $unionListingAutoCategorySelect = $connection->select()
            ->from($listingAutoCategoryGroupTable, ['result_field'=>'adding_template_other_category_id'])
            ->where('adding_template_other_category_id IS NOT NULL');
        $unionSelectListingProductTemplate = $connection->select()
            ->from($listingProductTable, ['result_field'=>'template_other_category_id'])
            ->where('template_other_category_id IS NOT NULL');

        $unionSelect = $connection->select()->union(
            [
                $unionListingAutoGlobalSelect,
                $unionListingAutoWebsiteSelect,
                $unionListingAutoCategorySelect,
                $unionSelectListingProductTemplate
            ]
        );

        $collection = $this->activeRecordFactory->getObject('Ebay_Template_OtherCategory')->getCollection();
        $collection->getSelect()->where('`id` NOT IN ('.$unionSelect->__toString().')');
        $collection->getSelect()->where('`create_date` < ?', $minCreateDate);

        $unusedTemplates = $collection->getItems();
        foreach ($unusedTemplates as $unusedTemplate) {
            $unusedTemplate->delete();
        }

        $this->getOperationHistory()->saveTimePoint(__METHOD__);
    }

    //########################################
}
