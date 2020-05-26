<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder\Price;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder\Price\Regular
 */
class Regular extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder\AbstractModel
{
    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getBuilderData()
    {
        $data = [];

        if (!isset($this->cachedData['regular_price'])) {
            $this->cachedData['regular_price'] = $this->getAmazonListingProduct()->getRegularPrice();
        }

        if (!isset($this->cachedData['regular_map_price'])) {
            $this->cachedData['regular_map_price'] = $this->getAmazonListingProduct()->getRegularMapPrice();
        }

        if (!isset($this->cachedData['regular_sale_price_info'])) {
            $salePriceInfo                                = $this->getAmazonListingProduct()->getRegularSalePriceInfo();
            $this->cachedData['regular_sale_price_info'] = $salePriceInfo;
        }

        $data['price'] = $this->cachedData['regular_price'];

        if ((float)$this->cachedData['regular_map_price'] <= 0) {
            $data['map_price'] = 0;
        } else {
            $data['map_price'] = $this->cachedData['regular_map_price'];
        }

        if ($this->cachedData['regular_sale_price_info'] === false) {
            $data['sale_price'] = 0;
        } else {
            $data['sale_price']            = $this->cachedData['regular_sale_price_info']['price'];
            $data['sale_price_start_date'] = $this->cachedData['regular_sale_price_info']['start_date'];
            $data['sale_price_end_date']   = $this->cachedData['regular_sale_price_info']['end_date'];
        }

        return $data;
    }

    //########################################
}
