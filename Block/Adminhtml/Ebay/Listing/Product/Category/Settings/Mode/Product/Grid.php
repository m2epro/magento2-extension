<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\Product;

use Ess\M2ePro\Helper\Component\Ebay\Category as eBayCategory;
use Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\Product\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\Manually\Grid
{
    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_listing_product_category_settings/stepTwoModeProductGrid', ['_current' => true]);
    }

    //########################################

    protected function additionalJs()
    {
        return 'EbayListingProductCategorySettingsModeProductGridObj.getSuggestedCategoriesForAll();';
    }

    //########################################
}
