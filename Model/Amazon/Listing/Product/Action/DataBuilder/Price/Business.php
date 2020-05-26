<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder\Price;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder\Price\Business
 */
class Business extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder\AbstractModel
{
    const BUSINESS_DISCOUNTS_TYPE_FIXED = 'fixed';

    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getBuilderData()
    {
        $data = [];

        if (!isset($this->cachedData['business_price'])) {
            $this->cachedData['business_price'] = $this->getAmazonListingProduct()->getBusinessPrice();
        }

        if (!isset($this->cachedData['business_discounts'])) {
            $this->cachedData['business_discounts'] = $this->getAmazonListingProduct()->getBusinessDiscounts();
        }

        $data['business_price'] = $this->cachedData['business_price'];

        if ($businessDiscounts = $this->cachedData['business_discounts']) {
            ksort($businessDiscounts);

            $data['business_discounts'] = [
                'type'   => self::BUSINESS_DISCOUNTS_TYPE_FIXED,
                'values' => $businessDiscounts
            ];
        }

        return $data;
    }

    //########################################
}
