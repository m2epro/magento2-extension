<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Log;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Log\Order
 */
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
        return $this->createBlock('Amazon_Account_Switcher')->setData([
            'component_mode' => $this->getComponentMode(),
        ]);
    }

    protected function createMarketplaceSwitcherBlock()
    {
        return $this->createBlock('Amazon_Marketplace_Switcher')->setData([
            'component_mode' => $this->getComponentMode(),
        ]);
    }

    //########################################
}
