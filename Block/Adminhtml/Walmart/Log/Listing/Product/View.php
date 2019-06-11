<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Log\Listing\Product;

use Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractView;

class View extends AbstractView
{
    //########################################

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\View\Walmart::NICK;
    }

    protected function createAccountSwitcherBlock()
    {
        return $this->createBlock('Walmart\Account\Switcher')->setData([
            'component_mode' => $this->getComponentMode(),
        ]);
    }

    protected function createMarketplaceSwitcherBlock()
    {
        return $this->createBlock('Walmart\Marketplace\Switcher')->setData([
            'component_mode' => $this->getComponentMode(),
        ]);
    }

    //########################################

    protected function _toHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock')->setData([
            'content' => $this->__(
                '<p>This Log contains all information about Actions, which were done on
                all M2E Pro and 3rd Party Listings and their Items.</p>'
            )
        ]);

        return $helpBlock->toHtml() . parent::_toHtml();
    }

    //########################################
}