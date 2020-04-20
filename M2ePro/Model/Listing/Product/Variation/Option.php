<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product\Variation;

/**
 * Class \Ess\M2ePro\Model\Listing\Product\Variation\Option
 */
class Option extends \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Listing\Product\Variation
     */
    private $listingProductVariationModel = null;

    /**
     * @var \Ess\M2ePro\Model\Magento\Product\Cache
     */
    protected $magentoProductModel = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation\Option');
    }

    //########################################

    public function afterSave()
    {
        $listingProductId = $this->getListingProduct()->getId();
        $variationId      = $this->getListingProductVariationId();

        $this->getHelper('Data_Cache_Runtime')->removeTagValues(
            "listing_product_{$listingProductId}_variation_{$variationId}_options"
        );

        return parent::afterSave();
    }

    public function beforeDelete()
    {
        $listingProductId = $this->getListingProduct()->getId();
        $variationId      = $this->getListingProductVariationId();

        $this->getHelper('Data_Cache_Runtime')->removeTagValues(
            "listing_product_{$listingProductId}_variation_{$variationId}_options"
        );

        return parent::beforeDelete();
    }

    public function delete()
    {
        if (!parent::delete()) {
            return false;
        }

        $this->listingProductVariationModel = null;
        $this->magentoProductModel = null;

        return true;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing\Product\Variation
     */
    public function getListingProductVariation()
    {
        if ($this->listingProductVariationModel === null) {
            $this->listingProductVariationModel = $this->parentFactory->getObjectLoaded(
                $this->getComponentMode(),
                'Listing_Product_Variation',
                $this->getData('listing_product_variation_id')
            );
        }

        return $this->listingProductVariationModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product\Variation $instance
     */
    public function setListingProductVariation(\Ess\M2ePro\Model\Listing\Product\Variation $instance)
    {
         $this->listingProductVariationModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     */
    public function getMagentoProduct()
    {
        if (!$this->magentoProductModel) {
            $this->magentoProductModel = $this->modelFactory->getObject('Magento_Product_Cache')
                ->setStoreId($this->getListing()->getStoreId())
                ->setProductId($this->getData('product_id'))
                ->setStatisticId($this->getListingProduct()->getId());
        }

        $this->getListingProduct()->getMagentoProduct()->isCacheEnabled()
            ? $this->magentoProductModel->enableCache() : $this->magentoProductModel->disableCache();

        return $this->magentoProductModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Magento\Product\Cache $instance
     */
    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product\Cache $instance)
    {
        $this->magentoProductModel = $instance;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    public function getListing()
    {
        return $this->getListingProductVariation()->getListing();
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    public function getListingProduct()
    {
        return $this->getListingProductVariation()->getListingProduct();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        return $this->getListingProductVariation()->getAccount();
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    public function getMarketplace()
    {
        return $this->getListingProductVariation()->getMarketplace();
    }

    //########################################

    /**
     * @return int
     */
    public function getListingProductVariationId()
    {
        return (int)$this->getData('listing_product_variation_id');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getProductId()
    {
        return (int)$this->getData('product_id');
    }

    /**
     * @return mixed
     */
    public function getProductType()
    {
        return $this->getData('product_type');
    }

    // ---------------------------------------

    public function getAttribute()
    {
         return $this->getData('attribute');
    }

    public function getOption()
    {
        return $this->getData('option');
    }

    //########################################
}
