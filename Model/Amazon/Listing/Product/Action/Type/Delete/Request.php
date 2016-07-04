<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Delete;

class Request extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Request
{
    //########################################

    /**
     * @return array
     */
    protected function getActionData()
    {
        return array(
            'sku' => $this->getAmazonListingProduct()->getSku()
        );
    }

    //########################################
}