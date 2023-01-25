<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\ScheduledStopAction;

use Ess\M2ePro\Model\Ebay\Listing\Product\ScheduledStopAction;

class Factory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): ScheduledStopAction
    {
        return $this->objectManager->create(ScheduledStopAction::class, $data);
    }
}
