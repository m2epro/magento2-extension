<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request;

class Price extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\AbstractModel
{
    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        if (!$this->getConfigurator()->isPriceAllowed()) {
            return array();
        }

        if (!isset($this->validatorsData['price'])) {
            $this->validatorsData['price'] = $this->getAmazonListingProduct()->getPrice();
        }

        if (!isset($this->validatorsData['map_price'])) {
            $this->validatorsData['map_price'] = $this->getAmazonListingProduct()->getMapPrice();
        }

        if (!isset($this->validatorsData['sale_price_info'])) {
            $this->validatorsData['sale_price_info'] = $this->getAmazonListingProduct()->getSalePriceInfo();
        }

        $data = array(
            'price' => $this->validatorsData['price'],
        );

        if ((float)$this->validatorsData['map_price'] <= 0) {
            $data['map_price'] = 0;
        } else {
            $data['map_price'] = $this->validatorsData['map_price'];
        }

        if ($this->validatorsData['sale_price_info'] === false) {
            $data['sale_price'] = 0;
        } else {
            $data['sale_price']            = $this->validatorsData['sale_price_info']['price'];
            $data['sale_price_start_date'] = $this->validatorsData['sale_price_info']['start_date'];
            $data['sale_price_end_date']   = $this->validatorsData['sale_price_info']['end_date'];
        }

        return $data;
    }

    //########################################
}