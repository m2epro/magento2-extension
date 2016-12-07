<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Log;

class Order extends \Ess\M2ePro\Block\Adminhtml\Log\Order\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_amazon_log_order';
    }

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\View\Amazon::NICK;
    }

    protected function createAccountSwitcherBlock()
    {
        return $this->createBlock('Amazon\Account\Switcher')->setData([
            'component_mode' => $this->getComponentMode(),
        ]);
    }

    protected function createMarketplaceSwitcherBlock()
    {
        return $this->createBlock('Amazon\Marketplace\Switcher')->setData([
            'component_mode' => $this->getComponentMode(),
        ]);
    }

    //########################################
}