<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product;

use Ess\M2ePro\Model\Exception;

/**
 * @method \Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation getResource()
 */
class Variation extends \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    private $listingProductModel = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation');
    }

    //########################################

    public function afterSave()
    {
        $this->getHelper('Data_Cache_Runtime')->removeTagValues(
            "listing_product_{$this->getListingProductId()}_variations"
        );

        return parent::afterSave();
    }

    public function beforeDelete()
    {
        $this->getHelper('Data_Cache_Runtime')->removeTagValues(
            "listing_product_{$this->getListingProductId()}_variations"
        );

        return parent::beforeDelete();
    }

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $options = $this->getOptions(true, [], true, false);
        foreach ($options as $option) {
            $option->delete();
        }

        $this->listingProductModel = null;

        $this->deleteChildInstance();

        return parent::delete();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    public function getListingProduct()
    {
        if ($this->listingProductModel === null) {
            $this->listingProductModel = $this->parentFactory->getObjectLoaded(
                $this->getComponentMode(),
                'Listing\Product',
                $this->getData('listing_product_id')
            );
        }

        return $this->listingProductModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $instance
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $instance)
    {
         $this->listingProductModel = $instance;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    public function getListing()
    {
        return $this->getListingProduct()->getListing();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        return $this->getListingProduct()->getAccount();
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    public function getMarketplace()
    {
        return $this->getListingProduct()->getMarketplace();
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @param bool $tryToGetFromStorage
     * @param bool $throwExceptionIfNoOptions
     * @return \Ess\M2ePro\Model\Listing\Product\Variation\Option[]
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getOptions(
        $asObjects = false,
        array $filters = [],
        $tryToGetFromStorage = true,
        $throwExceptionIfNoOptions = true
    ) {
        $storageKey = "listing_product_{$this->getListingProductId()}_variation_{$this->getId()}_options_" .
                      sha1((string)$asObjects . $this->getHelper('Data')->jsonEncode($filters));

        if ($tryToGetFromStorage && ($cacheData = $this->getHelper('Data_Cache_Runtime')->getValue($storageKey))) {
            return $cacheData;
        }

        /** @var $options \Ess\M2ePro\Model\Listing\Product\Variation\Option[] */
        $options = $this->getRelatedComponentItems(
            'Listing_Product_Variation_Option',
            'listing_product_variation_id',
            $asObjects,
            $filters
        );

        if ($throwExceptionIfNoOptions && count($options) <= 0) {
            throw new Exception\Logic(
                'There are no options for a variation product.',
                [
                    'variation_id'       => $this->getId(),
                    'listing_product_id' => $this->getListingProductId()
                ]
            );
        }

        if ($asObjects) {
            foreach ($options as $option) {
                $option->setListingProductVariation($this);
            }
        }

        $this->getHelper('Data_Cache_Runtime')->setValue($storageKey, $options, [
            'listing_product',
            "listing_product_{$this->getListingProductId()}",
            "listing_product_{$this->getListingProductId()}_variation_{$this->getId()}",
            "listing_product_{$this->getListingProductId()}_variation_{$this->getId()}_options"
        ]);

        return $options;
    }

    //########################################

    /**
     * @return int
     */
    public function getListingProductId()
    {
        return (int)$this->getData('listing_product_id');
    }

     //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getAdditionalData()
    {
        return $this->getSettings('additional_data');
    }

     //########################################
}
