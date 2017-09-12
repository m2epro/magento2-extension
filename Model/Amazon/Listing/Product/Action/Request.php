<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action;

abstract class Request extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var array
     */
    private $params = array();

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator
     */
    private $configurator = NULL;

    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    private $listingProduct = NULL;

    /**
     * @var array
     */
    private $warningMessages = array();

    //########################################

    abstract public function getRequestData();

    //########################################

    /**
     * @param array $params
     */
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
        return $this->getAmazonAccount()->getMarketplace();
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
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product
     */
    protected function getAmazonListingProduct()
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
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager
     */
    protected function getVariationManager()
    {
        return $this->getAmazonListingProduct()->getVariationManager();
    }

    //########################################

    protected function addWarningMessage($message)
    {
        $this->warningMessages[md5($message)] = $message;
    }

    /**
     * @return array
     */
    public function getWarningMessages()
    {
        return $this->warningMessages;
    }

    //########################################
}