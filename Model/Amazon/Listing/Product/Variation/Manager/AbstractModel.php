<?php

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager;

abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    private $listingProduct = null;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager
     */
    private $variationManager = null;
    /** @var bool  */
    private $isCacheEnabled = false;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory  */
    protected $activeRecordFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory  */
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->amazonFactory = $amazonFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function setVariationManager(
        \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager $variationManager
    ): void {
        $this->variationManager = $variationManager;
        $this->listingProduct = $variationManager->getListingProduct();
    }

    public function getVariationManager(): \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager
    {
        return $this->variationManager;
    }

    // ---------------------------------------

    public function getListingProduct(): \Ess\M2ePro\Model\Listing\Product
    {
        return $this->listingProduct;
    }

    public function getAmazonListingProduct(): \Ess\M2ePro\Model\Amazon\Listing\Product
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
     * @return \Ess\M2ePro\Model\Amazon\Listing
     */
    public function getAmazonListing()
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
        return $this->getAmazonListingProduct()->getActualMagentoProduct();
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
