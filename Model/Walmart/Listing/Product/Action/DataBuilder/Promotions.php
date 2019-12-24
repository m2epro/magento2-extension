<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ss-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\Promotions
 */
class Promotions extends \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\AbstractModel
{
    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        $data = [];

        if (!isset($this->cachedData['promotions'])) {
            $this->cachedData['promotions'] = $this->getWalmartListingProduct()->getPromotions();
        }

        $data['promotion_prices'] = $this->cachedData['promotions'];

        return $data;
    }

    //########################################
}
