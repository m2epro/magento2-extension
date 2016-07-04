<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class GetCategoryChooserHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        // ---------------------------------------
        $listingId = $this->getRequest()->getParam('id');
        $listingProductIds = $this->getRequestIds();
        $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $listingId);
        // ---------------------------------------

        $internalData = array();

        // ---------------------------------------
        $categoryTemplateIds  = $this->activeRecordFactory
                                    ->getObject('Ebay\Listing\Product')
                                    ->getResource()
                                    ->getTemplateCategoryIds($listingProductIds);
        $internalData = array_merge(
            $internalData,
            $this->getHelper('Component\Ebay\Category\Ebay')->getSameTemplatesData($categoryTemplateIds)
        );
        // ---------------------------------------
        $otherCategoryTemplateIds = $this->activeRecordFactory
                                        ->getObject('Ebay\Listing\Product')
                                        ->getResource()
                                        ->getTemplateOtherCategoryIds($listingProductIds);

        $internalData = array_merge(
            $internalData,
            $this->getHelper('Component\Ebay\Category\Store')->getSameTemplatesData($otherCategoryTemplateIds)
        );
        // ---------------------------------------

        /* @var $chooserBlock \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Chooser */
        $chooserBlock = $this->createBlock('Ebay\Listing\Product\Category\Settings\Chooser');
        $chooserBlock->setDivId('chooser_main_container');
        $chooserBlock->setAccountId($listing->getAccountId());
        $chooserBlock->setMarketplaceId($listing->getMarketplaceId());
        $chooserBlock->setInternalData($internalData);

        // ---------------------------------------
        $wrapper = $this->createBlock('Ebay\Listing\View\Settings\Category\Chooser\Wrapper');
        $wrapper->setChild('chooser', $chooserBlock);
        // ---------------------------------------

        $this->setAjaxContent($wrapper);
        return $this->getResult();
    }

    //########################################
}