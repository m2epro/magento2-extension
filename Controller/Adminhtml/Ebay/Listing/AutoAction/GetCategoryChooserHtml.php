<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction\GetCategoryChooserHtml
 */
class GetCategoryChooserHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->ebayFactory->getCachedObjectLoaded(
            'Listing',
            $this->getRequest()->getParam('listing_id')
        );

        $magentoCategoryId = $this->getRequest()->getParam('magento_category_id');

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\Converter $converter */
        $converter = $this->modelFactory->getObject('Ebay_Template_Category_Chooser_Converter');
        $converter->setAccountId($listing->getAccountId());
        $converter->setMarketplaceId($listing->getMarketplaceId());

        $categoryTemplate = $this->getCategoryTemplate(
            $listing,
            \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN,
            $this->getRequest()->getParam('auto_mode'),
            $this->getRequest()->getParam('group_id'),
            $magentoCategoryId
        );
        if ($categoryTemplate !== null) {
            $converter->setCategoryDataFromTemplate(
                $categoryTemplate->getData(),
                \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN
            );
        }

        $categorySecondaryTemplate = $this->getCategoryTemplate(
            $listing,
            \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_SECONDARY,
            $this->getRequest()->getParam('auto_mode'),
            $this->getRequest()->getParam('group_id'),
            $magentoCategoryId
        );
        if ($categorySecondaryTemplate !== null) {
            $converter->setCategoryDataFromTemplate(
                $categorySecondaryTemplate->getData(),
                \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_SECONDARY
            );
        }

        $storeTemplate = $this->getStoreCategoryTemplate(
            $listing,
            \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_MAIN,
            $this->getRequest()->getParam('auto_mode'),
            $this->getRequest()->getParam('group_id'),
            $magentoCategoryId
        );
        if ($storeTemplate !== null) {
            $converter->setCategoryDataFromTemplate(
                $storeTemplate->getData(),
                \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_MAIN
            );
        }

        $storeSecondaryTemplate = $this->getStoreCategoryTemplate(
            $listing,
            \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_SECONDARY,
            $this->getRequest()->getParam('auto_mode'),
            $this->getRequest()->getParam('group_id'),
            $magentoCategoryId
        );
        if ($storeSecondaryTemplate !== null) {
            $converter->setCategoryDataFromTemplate(
                $storeSecondaryTemplate->getData(),
                \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_SECONDARY
            );
        }

        /** @var $chooserBlock \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser */
        $chooserBlock = $this->createBlock('Ebay_Template_Category_Chooser');
        $chooserBlock->setAccountId($listing->getAccountId());
        $chooserBlock->setMarketplaceId($listing->getMarketplaceId());
        $chooserBlock->setCategoriesData($converter->getCategoryDataForChooser());

        $this->setAjaxContent($chooserBlock);
        return $this->getResult();
    }

    //########################################
}
