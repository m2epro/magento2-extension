<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    private $listingProduct = null;

    /**
     * @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager
     */
    private $variationManager = null;

    private $isCacheEnabled = false;

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager $variationManager
     */
    public function setVariationManager(\Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager $variationManager)
    {
        $this->variationManager = $variationManager;
        $this->listingProduct = $variationManager->getListingProduct();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager
     */
    public function getVariationManager()
    {
        return $this->variationManager;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    public function getListingProduct()
    {
        return $this->listingProduct;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product
     */
    public function getWalmartListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    public function getListing()
    {
        return $this->getListingProduct()->getListing();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing
     */
    public function getWalmartListing()
    {
        return $this->getListing()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     */
    public function getMagentoProduct()
    {
        return $this->getListingProduct()->getMagentoProduct();
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     */
    public function getActualMagentoProduct()
    {
        return $this->getWalmartListingProduct()->getActualMagentoProduct();
    }

    //########################################

    /**
     * @return bool
     */
    public function isCacheEnabled()
    {
        return $this->isCacheEnabled;
    }

    /**
     * @return $this
     */
    public function enableCache()
    {
        $this->isCacheEnabled = true;

        $this->getMagentoProduct()->enableCache();
        $this->getActualMagentoProduct()->enableCache();

        return $this;
    }

    /**
     * @return $this
     */
    public function disableCache()
    {
        $this->isCacheEnabled = false;

        $this->getMagentoProduct()->disableCache();
        $this->getActualMagentoProduct()->disableCache();

        return $this;
    }

    //########################################

    abstract public function clearTypeData();

    //########################################
}
