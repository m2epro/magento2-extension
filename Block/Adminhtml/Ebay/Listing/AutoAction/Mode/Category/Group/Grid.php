<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode\Category\Group;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\Category\Group\Grid
{
    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_listing_autoAction/getCategoryGroupGrid', array('_current' => true));
    }

    //########################################
}