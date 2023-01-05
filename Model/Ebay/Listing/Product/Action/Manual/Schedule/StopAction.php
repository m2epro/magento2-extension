<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Schedule;

class StopAction extends AbstractSchedule
{
    protected function getAction(): int
    {
        return \Ess\M2ePro\Model\Listing\Product::ACTION_STOP;
    }

    protected function prepareOrFilterProducts(array $listingsProducts): array
    {
        return $listingsProducts;
    }
}
