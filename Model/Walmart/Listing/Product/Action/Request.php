<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Request
 */
abstract class Request extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var array
     */
    private $params = [];

    /**
     * @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator
     */
    private $configurator = null;

    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    private $listingProduct = null;

    /**
     * @var array
     */
    private $warningMessages = [];

    /**
     * @var array
     */
    protected $metaData = [];

    //########################################

    abstract public function getRequestData();

    //########################################

    /**
     * @param array $params
     */
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

    /**
     * @param \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $object
     */
    public function setConfigurator(\Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $object)
    {
        $this->configurator = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator
     */
    protected function getConfigurator()
    {
        return $this->configurator;
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

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    protected function getMarketplace()
    {
        return $this->getWalmartAccount()->getMarketplace();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Marketplace
     */
    protected function getWalmartMarketplace()
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
     * @return \Ess\M2ePro\Model\Walmart\Account
     */
    protected function getWalmartAccount()
    {
        return $this->getAccount()->getChildObject();
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
     * @return \Ess\M2ePro\Model\Walmart\Listing
     */
    protected function getWalmartListing()
    {
        return $this->getListing()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product
     */
    protected function getWalmartListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    protected function getMagentoProduct()
    {
        return $this->getListingProduct()->getMagentoProduct();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager
     */
    protected function getVariationManager()
    {
        return $this->getWalmartListingProduct()->getVariationManager();
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
}
