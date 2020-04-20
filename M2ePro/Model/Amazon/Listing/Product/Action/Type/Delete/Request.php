<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Delete;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Delete\Request
 */
class Request extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Request
{
    //########################################

    /**
     * @return array
     */
    protected function getActionData()
    {
        return [
            'sku' => $this->getAmazonListingProduct()->getSku()
        ];
    }

    //########################################
}
