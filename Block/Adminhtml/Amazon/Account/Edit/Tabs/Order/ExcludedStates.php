<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class ExcludedStates extends AbstractBlock
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    protected $amazonHelper;

    protected $_template = 'amazon/account/order/excludedStates.phtml';

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->amazonHelper = $amazonHelper;
    }

    //########################################

    public function getSelectedStates()
    {
        return $this->getData('selected_states');
    }

    public function getStatesList()
    {
        return array_chunk($this->amazonHelper->getStatesList(), 8, true);
    }

    //########################################
}
