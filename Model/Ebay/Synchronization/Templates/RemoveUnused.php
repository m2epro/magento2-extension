<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\Templates;

class RemoveUnused extends AbstractModel
{
    const SAFE_CREATE_DATE_INTERVAL = 86400;

    //########################################

    /**
     * @return string
     */
    protected function getNick()
    {
        return '/remove_unused/';
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return 'Remove Unused Policies';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 0;
    }

    protected function getPercentsEnd()
    {
        return 20;
    }

    // ---------------------------------------

    protected function intervalIsEnabled()
    {
        return true;
    }

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

    private function removeUnusedTemplates($templateNick)
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__.$templateNick,
                                                         'Remove Unused "'.$templateNick.'" Policies');

        /** @var \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager */
        $templateManager = $this->modelFactory->getObject('Ebay\Template\Manager')->setTemplate($templateNick);

        $connRead = $this->resourceConnection->getConnection();

        $listingTable = $this->activeRecordFactory->getObject('Ebay\Listing')->getResource()->getMainTable();
        $listingProductTable = $this->activeRecordFactory->getObject('Ebay\Listing\Product')->getResource()
            ->getMainTable();

        $unionSelectListingTemplate = $connRead->select()
                    ->from($listingTable,array('result_field'=>$templateManager->getTemplateIdColumnName()))
                    ->where($templateManager->getTemplateIdColumnName().' IS NOT NULL');
        $unionSelectListingCustom = $connRead->select()
                     ->from($listingTable,array('result_field'=>$templateManager->getCustomIdColumnName()))
                     ->where($templateManager->getCustomIdColumnName().' IS NOT NULL');
        $unionSelectListingProductTemplate = $connRead->select()
                     ->from($listingProductTable,array('result_field'=>$templateManager->getTemplateIdColumnName()))
                     ->where($templateManager->getTemplateIdColumnName().' IS NOT NULL');
        $unionSelectListingProductCustom = $connRead->select()
                     ->from($listingProductTable,array('result_field'=>$templateManager->getCustomIdColumnName()))
                     ->where($templateManager->getCustomIdColumnName().' IS NOT NULL');

        $unionSelect = $connRead->select()->union(array(
            $unionSelectListingTemplate,
            $unionSelectListingCustom,
            $unionSelectListingProductTemplate,
            $unionSelectListingProductCustom
        ));

        $minCreateDate = $this->getHelper('Data')->getCurrentGmtDate(true) - self::SAFE_CREATE_DATE_INTERVAL;
        $minCreateDate = $this->getHelper('Data')->getDate($minCreateDate);

        $collection = $templateManager->getTemplateCollection();
        $collection->getSelect()->where('`id` NOT IN ('.$unionSelect->__toString().')');
        $collection->getSelect()->where('`is_custom_template` = 1');
        $collection->getSelect()->where('`create_date` < ?',$minCreateDate);

        $unusedTemplates = $collection->getItems();
        foreach ($unusedTemplates as $unusedTemplate) {
            $unusedTemplate->delete();
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__.$templateNick);
    }

    // ---------------------------------------

    private function removeCategoriesTemplates()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Remove Unused "Category" Policies');

        $connRead = $this->resourceConnection->getConnection();

        $listingTable = $this->activeRecordFactory->getObject('Ebay\Listing')->getResource()->getMainTable();
        $listingProductTable = $this->activeRecordFactory->getObject('Ebay\Listing\Product')->getResource()
            ->getMainTable();
        $listingAutoCategoryGroupTable = $this->activeRecordFactory->getObject('Ebay\Listing\Auto\Category\Group')
                                                            ->getResource()
                                                            ->getMainTable();

        $minCreateDate = $this->getHelper('Data')->getCurrentGmtDate(true) - self::SAFE_CREATE_DATE_INTERVAL;
        $minCreateDate = $this->getHelper('Data')->getDate($minCreateDate);

        $unionListingAutoGlobalSelect = $connRead->select()
                    ->from($listingTable,array('result_field'=>'auto_global_adding_template_category_id'))
                    ->where('auto_global_adding_template_category_id IS NOT NULL');
        $unionListingAutoWebsiteSelect = $connRead->select()
                    ->from($listingTable,array('result_field'=>'auto_website_adding_template_category_id'))
                    ->where('auto_website_adding_template_category_id IS NOT NULL');
        $unionListingAutoCategorySelect = $connRead->select()
                    ->from($listingAutoCategoryGroupTable,array('result_field'=>'adding_template_category_id'))
                    ->where('adding_template_category_id IS NOT NULL');
        $unionSelectListingProductTemplate = $connRead->select()
                    ->from($listingProductTable,array('result_field'=>'template_category_id'))
                    ->where('template_category_id IS NOT NULL');

        $unionSelect = $connRead->select()->union(array(
            $unionListingAutoGlobalSelect,
            $unionListingAutoWebsiteSelect,
            $unionListingAutoCategorySelect,
            $unionSelectListingProductTemplate
        ));

        $collection = $this->activeRecordFactory->getObject('Ebay\Template\Category')->getCollection();
        $collection->getSelect()->where('`id` NOT IN ('.$unionSelect->__toString().')');
        $collection->getSelect()->where('`create_date` < ?',$minCreateDate);

        $unusedTemplates = $collection->getItems();
        foreach ($unusedTemplates as $unusedTemplate) {
            $unusedTemplate->delete();
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    private function removeOtherCategoriesTemplates()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Remove Unused "Other Category" Policies');

        $connRead = $this->resourceConnection->getConnection();

        $listingTable = $this->activeRecordFactory->getObject('Ebay\Listing')->getResource()->getMainTable();
        $listingProductTable = $this->activeRecordFactory->getObject('Ebay\Listing\Product')->getResource()
            ->getMainTable();
        $listingAutoCategoryGroupTable = $this->activeRecordFactory->getObject('Ebay\Listing\Auto\Category\Group')
                                                            ->getResource()
                                                            ->getMainTable();

        $minCreateDate = $this->getHelper('Data')->getCurrentGmtDate(true) - self::SAFE_CREATE_DATE_INTERVAL;
        $minCreateDate = $this->getHelper('Data')->getDate($minCreateDate);

        $unionListingAutoGlobalSelect = $connRead->select()
                    ->from($listingTable,array('result_field'=>'auto_global_adding_template_other_category_id'))
                    ->where('auto_global_adding_template_other_category_id IS NOT NULL');
        $unionListingAutoWebsiteSelect = $connRead->select()
                    ->from($listingTable,array('result_field'=>'auto_website_adding_template_other_category_id'))
                    ->where('auto_website_adding_template_other_category_id IS NOT NULL');
        $unionListingAutoCategorySelect = $connRead->select()
                    ->from($listingAutoCategoryGroupTable,array('result_field'=>'adding_template_other_category_id'))
                    ->where('adding_template_other_category_id IS NOT NULL');
        $unionSelectListingProductTemplate = $connRead->select()
                    ->from($listingProductTable,array('result_field'=>'template_other_category_id'))
                    ->where('template_other_category_id IS NOT NULL');

        $unionSelect = $connRead->select()->union(array(
            $unionListingAutoGlobalSelect,
            $unionListingAutoWebsiteSelect,
            $unionListingAutoCategorySelect,
            $unionSelectListingProductTemplate
        ));

        $collection = $this->activeRecordFactory->getObject('Ebay\Template\OtherCategory')->getCollection();
        $collection->getSelect()->where('`id` NOT IN ('.$unionSelect->__toString().')');
        $collection->getSelect()->where('`create_date` < ?',$minCreateDate);

        $unusedTemplates = $collection->getItems();
        foreach ($unusedTemplates as $unusedTemplate) {
            $unusedTemplate->delete();
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    //########################################
}