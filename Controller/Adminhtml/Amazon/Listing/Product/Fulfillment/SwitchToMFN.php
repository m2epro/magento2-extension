<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Fulfillment;

class SwitchToMFN extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\RunReviseProducts
{
    public function execute()
    {
        return $this->scheduleAction(
            \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
            [
                'switch_to' => \Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder\Qty::FULFILLMENT_MODE_MFN,
            ]
        );
    }
}
