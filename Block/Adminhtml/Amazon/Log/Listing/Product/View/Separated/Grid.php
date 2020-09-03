<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Log\Listing\Product\View\Separated;

use Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\View\Separated\AbstractGrid;
use Ess\M2ePro\Block\Adminhtml\Amazon\Log\Listing\Product\GridTrait;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Log\Listing\Product\View\Separated\Grid
 */
class Grid extends AbstractGrid
{
    //########################################

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    protected function getExcludedActionTitles()
    {
        return [
            \Ess\M2ePro\Model\Listing\Log::ACTION_RESET_BLOCKED_PRODUCT => ''
        ];
    }

    //########################################
}
