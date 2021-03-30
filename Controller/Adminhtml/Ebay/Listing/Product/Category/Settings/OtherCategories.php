<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode as CategoryTemplateBlock;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings\OtherCategories
 */
class OtherCategories extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings\Index
{
    public function execute()
    {
        $this->listing = $this->getListingFromRequest();

        $this->setWizardStep('categoryStepTwo');
        $this->clearSession();

        $this->setSessionValue('mode', CategoryTemplateBlock::MODE_PRODUCT);
        $this->initSessionDataProducts($this->listing->getChildObject()->getAddedListingProductsIds());

        $block = $this->createBlock('Ebay_Listing_Product_Category_Settings_Other_Product');
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Set eBay Category'));

        $categoriesData = $this->getSessionValue($this->getSessionDataKey());
        $block->getChildBlock('grid')->setCategoriesData($categoriesData);
        $this->addContent($block);

        return $this->getResult();
    }
}
