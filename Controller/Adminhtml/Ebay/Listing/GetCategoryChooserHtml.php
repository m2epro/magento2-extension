<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

use \Ess\M2ePro\Helper\Component\Ebay\Category as eBayCategory;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\GetCategoryChooserHtml
 */
class GetCategoryChooserHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $lPIds = $this->getRequestIds('products_id');
        $accountId = $this->getRequest()->getParam('account_id');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');

        $productResource = $this->activeRecordFactory->getObject('Ebay_Listing_Product')->getResource();

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\Converter $converter */
        $converter = $this->modelFactory->getObject('Ebay_Template_Category_Chooser_Converter');
        $accountId && $converter->setAccountId($accountId);
        $marketplaceId && $converter->setMarketplaceId($marketplaceId);

        $ids = $productResource->getTemplateCategoryIds($lPIds, 'template_category_id', true);
        $template = $this->tryToLoadCategoryTemplate($ids);
        if ($template && $template->getId()) {
            $converter->setCategoryDataFromTemplate($template->getData(), eBayCategory::TYPE_EBAY_MAIN);
        }

        $ids = $productResource->getTemplateCategoryIds($lPIds, 'template_category_secondary_id', true);
        $template = $this->tryToLoadCategoryTemplate($ids);
        if ($template && $template->getId()) {
            $converter->setCategoryDataFromTemplate($template->getData(), eBayCategory::TYPE_EBAY_SECONDARY);
        }

        $ids = $productResource->getTemplateCategoryIds($lPIds, 'template_store_category_id', true);
        $template = $this->tryToLoadStoreCategoryTemplate($ids);
        if ($template && $template->getId()) {
            $converter->setCategoryDataFromTemplate($template->getData(), eBayCategory::TYPE_STORE_MAIN);
        }

        $ids = $productResource->getTemplateCategoryIds($lPIds, 'template_store_category_secondary_id', true);
        $template = $this->tryToLoadStoreCategoryTemplate($ids);
        if ($template && $template->getId()) {
            $converter->setCategoryDataFromTemplate($template->getData(), eBayCategory::TYPE_STORE_SECONDARY);
        }

        /** @var $chooserBlock \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Chooser */
        $chooserBlock = $this->createBlock('Ebay_Listing_Product_Category_Settings_Chooser');
        $accountId && $chooserBlock->setAccountId($accountId);
        $marketplaceId && $chooserBlock->setMarketplaceId($marketplaceId);
        $chooserBlock->setCategoryMode($this->getRequest()->getParam('category_mode'));
        $chooserBlock->setCategoriesData($converter->getCategoryDataForChooser());

        $this->setAjaxContent($chooserBlock->toHtml());
        return $this->getResult();
    }

    //########################################

    protected function tryToLoadCategoryTemplate($ids)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Template\Category $template */
        $template = $this->activeRecordFactory->getObject('Ebay_Template_Category');

        if (empty($ids)) {
            return $template;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category\Collection $collection */
        $collection = $template->getCollection();
        $collection->addFieldToFilter('id', ['in' => $ids]);

        if (count($ids) !== $collection->getSize()) {
            // @codingStandardsIgnoreLine
            return $template;
        }

        if (count($ids) === 1) {
            // @codingStandardsIgnoreLine
            return $collection->getFirstItem();
        }

        $differentCategories = [];
        $isCustomTemplate = 0;
        foreach ($collection->getItems() as $item) {
            /**@var \Ess\M2ePro\Model\Ebay\Template\Category $item */
            $differentCategories[] = $item->getCategoryValue();

            if (!empty($item->getIsCustomTemplate())) {
                $isCustomTemplate = $item->getIsCustomTemplate();
            }
        }

        if (count(array_unique($differentCategories)) > 1) {
            return $template;
        }

        if ($isCustomTemplate) {
            $collection->addFieldToFilter('is_custom_template', $isCustomTemplate);
        }

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category $tempTemplate */
        // @codingStandardsIgnoreLine
        $tempTemplate = $collection->getFirstItem();

        $template = $this->activeRecordFactory->getObject('Ebay_Template_Category');
        $template->loadByCategoryValue(
            $tempTemplate->getCategoryValue(),
            $tempTemplate->getCategoryMode(),
            $tempTemplate->getMarketplaceId(),
            $isCustomTemplate
        );

        return $template;
    }

    protected function tryToLoadStoreCategoryTemplate($ids)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory $template */
        $template = $this->activeRecordFactory->getObject('Ebay_Template_StoreCategory');

        if (empty($ids)) {
            return $template;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Template\StoreCategory\Collection $collection */
        $collection = $template->getCollection();
        $collection->addFieldToFilter('id', ['in' => $ids]);

        if (count($ids) !== $collection->getSize()) {
            // @codingStandardsIgnoreLine
            return $template;
        }

        if (count($ids) === 1) {
            // @codingStandardsIgnoreLine
            return $collection->getFirstItem();
        }

        $differentCategories = [];
        foreach ($collection->getItems() as $item) {
            /**@var \Ess\M2ePro\Model\Ebay\Template\StoreCategory $item */
            $differentCategories[] = $item->getCategoryValue();
        }

        if (count(array_unique($differentCategories)) > 1) {
            return $template;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory $tempTemplate */
        // @codingStandardsIgnoreLine
        $tempTemplate = $collection->getFirstItem();

        $template = $this->activeRecordFactory->getObject('Ebay_Template_StoreCategory');
        $template->loadByCategoryValue(
            $tempTemplate->getCategoryValue(),
            $tempTemplate->getCategoryMode(),
            $tempTemplate->getAccountId()
        );

        return $template;
    }

    //########################################
}
