<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Fulfillment;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Fulfillment\SwitchToAFN
 */
class SwitchToAFN extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\RunReviseProducts
{
    public function execute()
    {
        return $this->scheduleAction(
            \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
            [
                'switch_to' => \Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder\Qty::FULFILLMENT_MODE_AFN,
            ]
        );
    }
}
