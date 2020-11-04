<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\Order\ExcludedStates
 */
class ExcludedStates extends AbstractBlock
{
    //########################################

    protected $_template = 'amazon/account/order/excludedStates.phtml';

    //########################################

    public function getSelectedStates()
    {
        return $this->getData('selected_states');
    }

    public function getStatesList()
    {
        return array_chunk($this->getHelper('Component_Amazon')->getStatesList(), 8, true);
    }

    //########################################
}
