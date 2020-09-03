<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Log\Listing\Product\View\Separated;

use Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\View\Separated\AbstractGrid;
use Ess\M2ePro\Block\Adminhtml\Ebay\Log\Listing\Product\GridTrait;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Log\Listing\Product\View\Separated\Grid
 */
class Grid extends AbstractGrid
{
    //########################################

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Ebay::NICK;
    }

    protected function getExcludedActionTitles()
    {
        return [
            \Ess\M2ePro\Model\Listing\Log::ACTION_DELETE_AND_REMOVE_PRODUCT     => '',
            \Ess\M2ePro\Model\Listing\Log::ACTION_DELETE_PRODUCT_FROM_COMPONENT => '',
            \Ess\M2ePro\Model\Listing\Log::ACTION_SWITCH_TO_AFN_ON_COMPONENT    => '',
            \Ess\M2ePro\Model\Listing\Log::ACTION_SWITCH_TO_MFN_ON_COMPONENT    => '',
            \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_TIER_PRICE     => '',
            \Ess\M2ePro\Model\Listing\Log::ACTION_RESET_BLOCKED_PRODUCT         => '',
        ];
    }

    //########################################
}
