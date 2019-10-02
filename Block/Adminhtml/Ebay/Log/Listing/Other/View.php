<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Log\Listing\Other;

use Ess\M2ePro\Block\Adminhtml\Log\Listing\Other\AbstractView;

/**
 * Class View
 * @package Ess\M2ePro\Block\Adminhtml\Ebay\Log\Listing\Other
 */
class View extends AbstractView
{
    //########################################

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\View\Ebay::NICK;
    }

    //########################################
}
