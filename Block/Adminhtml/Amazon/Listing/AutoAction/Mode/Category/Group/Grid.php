<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AutoAction\Mode\Category\Group;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AutoAction\Mode\Category\Group\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\Category\Group\Grid
{
    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/amazon_listing_autoAction/getCategoryGroupGrid', ['_current' => true]);
    }

    //########################################
}
