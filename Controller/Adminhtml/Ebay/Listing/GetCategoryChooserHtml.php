<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\GetCategoryChooserHtml
 */
class GetCategoryChooserHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        // ---------------------------------------
        $listingId = $this->getRequest()->getParam('id');
        $listingProductIds = $this->getRequestIds('product_id');
        $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $listingId);
        // ---------------------------------------

        $internalData = [];

        // ---------------------------------------
        $categoryTemplateIds  = $this->activeRecordFactory
                                    ->getObject('Ebay_Listing_Product')
                                    ->getResource()
                                    ->getTemplateCategoryIds($listingProductIds);
        $internalData = array_merge(
            $internalData,
            $this->getHelper('Component_Ebay_Category_Ebay')->getSameTemplatesData($categoryTemplateIds)
        );
        // ---------------------------------------
        $otherCategoryTemplateIds = $this->activeRecordFactory
                                        ->getObject('Ebay_Listing_Product')
                                        ->getResource()
                                        ->getTemplateOtherCategoryIds($listingProductIds);

        $internalData = array_merge(
            $internalData,
            $this->getHelper('Component_Ebay_Category_Store')->getSameTemplatesData($otherCategoryTemplateIds)
        );
        // ---------------------------------------

        /** @var $chooserBlock \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Chooser */
        $chooserBlock = $this->createBlock('Ebay_Listing_Product_Category_Settings_Chooser');
        $chooserBlock->setDivId('chooser_main_container');
        $chooserBlock->setAccountId($listing->getAccountId());
        $chooserBlock->setMarketplaceId($listing->getMarketplaceId());
        $chooserBlock->setInternalData($internalData);

        // ---------------------------------------
        $wrapper = $this->createBlock('Ebay_Listing_View_Settings_Category_Chooser_Wrapper');
        $wrapper->setChild('chooser', $chooserBlock);
        // ---------------------------------------

        $this->setAjaxContent($wrapper);
        return $this->getResult();
    }

    //########################################
}
