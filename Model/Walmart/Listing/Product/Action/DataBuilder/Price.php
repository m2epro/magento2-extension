<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ss-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\Price
 */
class Price extends \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\AbstractModel
{
    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        $data = [];

        if (!isset($this->cachedData['price'])) {
            $this->cachedData['price'] = $this->getWalmartListingProduct()->getPrice();
        }

        $data['price'] = $this->cachedData['price'];

        return $data;
    }

    //########################################
}
