<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type;

abstract class Response extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var array
     */
    private $params = array();

    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    private $listingProduct = NULL;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator
     */
    private $configurator = NULL;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData
     */
    protected $requestData = NULL;

    //########################################

    abstract public function processSuccess($params = array());

    //########################################

    public function setParams(array $params = array())
    {
        $this->params = $params;
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->params;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $object
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $object)
    {
        $this->listingProduct = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    protected function getListingProduct()
    {
        return $this->listingProduct;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $object
     */
    public function setConfigurator(\Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $object)
    {
        $this->configurator = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator
     */
    protected function getConfigurator()
    {
        return $this->configurator;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData $object
     */
    public function setRequestData(\Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData $object)
    {
        $this->requestData = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Action\RequestData
     */
    protected function getRequestData()
    {
        return $this->requestData;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product
     */
    protected function getAmazonListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    protected function getListing()
    {
        return $this->getListingProduct()->getListing();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing
     */
    protected function getAmazonListing()
    {
        return $this->getListing()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    protected function getMarketplace()
    {
        return $this->getListing()->getMarketplace();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Marketplace
     */
    protected function getAmazonMarketplace()
    {
        return $this->getMarketplace()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    protected function getAccount()
    {
        return $this->getListing()->getAccount();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Account
     */
    protected function getAmazonAccount()
    {
        return $this->getAccount()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    protected function getMagentoProduct()
    {
        return $this->getListingProduct()->getMagentoProduct();
    }

    //########################################

    protected function appendStatusChangerValue($data)
    {
        if (isset($this->params['status_changer'])) {
            $data['status_changer'] = (int)$this->params['status_changer'];
        }

        return $data;
    }

    // ---------------------------------------

    protected function appendQtyValues($data)
    {
        if (!$this->getRequestData()->hasQty()) {
            return $data;
        }

        $data['online_qty'] = (int)$this->getRequestData()->getQty();

        if ((int)$data['online_qty'] > 0) {
            $data['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
        } else {
            $data['status'] = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
        }

        return $data;
    }

    protected function appendRegularPriceValues($data)
    {
        if (!$this->getRequestData()->hasRegularPrice()) {
            return $data;
        }

        $data['online_regular_price'] = (float)$this->getRequestData()->getRegularPrice();

        $data['online_regular_sale_price'] = NULL;
        $data['online_regular_sale_price_start_date'] = NULL;
        $data['online_regular_sale_price_end_date'] = NULL;

        if ($this->getRequestData()->hasRegularSalePrice()) {

            $salePrice = (float)$this->getRequestData()->getRegularSalePrice();

            if ($salePrice > 0) {
                $data['online_regular_sale_price'] = $salePrice;
                $data['online_regular_sale_price_start_date'] = $this->getRequestData()->getRegularSalePriceStartDate();
                $data['online_regular_sale_price_end_date'] = $this->getRequestData()->getRegularSalePriceEndDate();
            } else {
                $data['online_regular_sale_price'] = 0;
            }
        }

        return $data;
    }

    protected function appendBusinessPriceValues($data)
    {
        if (!$this->getRequestData()->hasBusinessPrice()) {
            return $data;
        }

        $data['online_business_price'] = (float)$this->getRequestData()->getBusinessPrice();

        if ($this->getRequestData()->hasBusinessDiscounts()) {
            $businessDiscounts = $this->getRequestData()->getBusinessDiscounts();
            $data['online_business_discounts'] = $this->getHelper('Data')->jsonEncode($businessDiscounts['values']);
        } else {
            $data['online_business_discounts'] = NULL;
        }

        return $data;
    }

    //########################################

    protected function setLastSynchronizationDates()
    {
        if (!$this->getConfigurator()->isQtyAllowed() && !$this->getConfigurator()->isRegularPriceAllowed()) {
            return;
        }

        $additionalData = $this->getListingProduct()->getAdditionalData();

        if ($this->getConfigurator()->isQtyAllowed()) {
            $additionalData['last_synchronization_dates']['qty'] = $this->getHelper('Data')->getCurrentGmtDate();
        }

        if ($this->getConfigurator()->isRegularPriceAllowed()) {
            $additionalData['last_synchronization_dates']['price'] = $this->getHelper('Data')->getCurrentGmtDate();
        }

        $this->getListingProduct()->setSettings('additional_data', $additionalData);
    }

    //########################################
}