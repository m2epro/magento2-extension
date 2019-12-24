<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Stop;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Stop\Request
 */
class Request extends \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Request
{
    //########################################

    protected function getActionData()
    {
        $data = array_merge(
            [
                'sku'  => $this->getWalmartListingProduct()->getSku(),
                'wpid' => $this->getWalmartListingProduct()->getWpid(),
                'qty' => 0
            ],
            // Only for CA marketplace
            $this->getLagTimeData()
        );

        return $data;
    }

    //########################################
}
