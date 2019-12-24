<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\Price
 */
class Price extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\AbstractModel
{
    const BUSINESS_DISCOUNTS_TYPE_FIXED = 'fixed';

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        $data = [];

        if ($this->getConfigurator()->isRegularPriceAllowed()) {
            if (!isset($this->validatorsData['regular_price'])) {
                $this->validatorsData['regular_price'] = $this->getAmazonListingProduct()->getRegularPrice();
            }

            if (!isset($this->validatorsData['regular_map_price'])) {
                $this->validatorsData['regular_map_price'] = $this->getAmazonListingProduct()->getRegularMapPrice();
            }

            if (!isset($this->validatorsData['regular_sale_price_info'])) {
                $salePriceInfo = $this->getAmazonListingProduct()->getRegularSalePriceInfo();
                $this->validatorsData['regular_sale_price_info'] = $salePriceInfo;
            }

            $data['price'] = $this->validatorsData['regular_price'];

            if ((float)$this->validatorsData['regular_map_price'] <= 0) {
                $data['map_price'] = 0;
            } else {
                $data['map_price'] = $this->validatorsData['regular_map_price'];
            }

            if ($this->validatorsData['regular_sale_price_info'] === false) {
                $data['sale_price'] = 0;
            } else {
                $data['sale_price']            = $this->validatorsData['regular_sale_price_info']['price'];
                $data['sale_price_start_date'] = $this->validatorsData['regular_sale_price_info']['start_date'];
                $data['sale_price_end_date']   = $this->validatorsData['regular_sale_price_info']['end_date'];
            }
        }

        if ($this->getConfigurator()->isBusinessPriceAllowed()) {
            if (!isset($this->validatorsData['business_price'])) {
                $this->validatorsData['business_price'] = $this->getAmazonListingProduct()->getBusinessPrice();
            }

            if (!isset($this->validatorsData['business_discounts'])) {
                $this->validatorsData['business_discounts'] = $this->getAmazonListingProduct()->getBusinessDiscounts();
            }

            $data['business_price'] = $this->validatorsData['business_price'];

            if ($businessDiscounts = $this->validatorsData['business_discounts']) {
                ksort($businessDiscounts);

                $data['business_discounts'] = [
                    'type'   => self::BUSINESS_DISCOUNTS_TYPE_FIXED,
                    'values' => $businessDiscounts
                ];
            }
        }

        return $data;
    }

    //########################################
}
