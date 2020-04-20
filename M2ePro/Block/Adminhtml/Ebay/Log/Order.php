<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Log;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Log\Order
 */
class Order extends \Ess\M2ePro\Block\Adminhtml\Log\Order\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_ebay_log_order';
    }

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\View\Ebay::NICK;
    }

    //########################################
}
