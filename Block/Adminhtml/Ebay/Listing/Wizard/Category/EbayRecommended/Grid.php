<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category\EbayRecommended;

use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category\ModeManually\Grid as ManualGrid;

class Grid extends ManualGrid
{
    protected function additionalJs()
    {
        return 'EbayListingProductCategorySettingsModeProductGridObj.getSuggestedCategoriesForAll();';
    }
}
