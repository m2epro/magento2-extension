<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request
 */
abstract class Request extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var array
     */
    protected $validatorsData = [];

    /**
     * @var array
     */
    private $params = [];

    /**
     * @var Configurator
     */
    private $configurator = null;

    /**
     * @var array
     */
    private $warningMessages = [];

    /**
     * @var array
     */
    protected $metaData = [];

    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    private $listingProduct = null;

    /**
     * @var bool
     */
    private $isVariationItem = false;

    //########################################

    public function setValidatorsData(array $data)
    {
        $this->validatorsData = $data;
    }

    /**
     * @return array
     */
    public function getValidatorsData()
    {
        return $this->validatorsData;
    }

    //########################################

    public function setParams(array $params = [])
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

    public function setConfigurator(Configurator $object)
    {
        $this->configurator = $object;
    }

    /**
     * @return Configurator
     */
    protected function getConfigurator()
    {
        return $this->configurator;
    }

    //########################################

    protected function addWarningMessage($message)
    {
        $this->warningMessages[sha1($message)] = $message;
    }

    /**
     * @return array
     */
    public function getWarningMessages()
    {
        return $this->warningMessages;
    }

    //########################################

    protected function addMetaData($key, $value)
    {
        $this->metaData[$key] = $value;
    }

    public function getMetaData()
    {
        return $this->metaData;
    }

    public function setMetaData($value)
    {
        $this->metaData = $value;
        return $this;
    }

    //########################################

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

    //########################################

    public function setIsVariationItem($value)
    {
        $this->isVariationItem = (bool)$value;
    }

    protected function getIsVariationItem()
    {
        return $this->isVariationItem;
    }

    //########################################

    abstract public function getRequestData();

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    protected function getMarketplace()
    {
        return $this->getListing()->getMarketplace();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Marketplace
     */
    protected function getEbayMarketplace()
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
     * @return \Ess\M2ePro\Model\Ebay\Account
     */
    protected function getEbayAccount()
    {
        return $this->getAccount()->getChildObject();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product
     */
    protected function getEbayListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     */
    protected function getMagentoProduct()
    {
        return $this->getListingProduct()->getMagentoProduct();
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
     * @return \Ess\M2ePro\Model\Ebay\Listing
     */
    protected function getEbayListing()
    {
        return $this->getListing()->getChildObject();
    }

    //########################################
}
