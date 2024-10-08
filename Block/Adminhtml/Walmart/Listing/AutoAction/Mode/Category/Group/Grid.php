<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AutoAction\Mode\Category\Group;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\Category\Group\AbstractGrid
{
    public function getGridUrl()
    {
        return $this->getUrl('*/walmart_listing_autoAction/getCategoryGroupGrid', ['_current' => true]);
    }
}
