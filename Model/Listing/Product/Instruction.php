<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product;

use Ess\M2ePro\Model\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Listing\Product\Instruction
 * @method \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction getResource()
 */
class Instruction extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    /** @var Product $listingProduct */
    protected $listingProduct = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction');
    }

    //########################################

    public function setListingProduct(Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getListingProduct()
    {
        if ($this->getId() === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Model must be loaded.');
        }

        if ($this->listingProduct === null) {
            $this->listingProduct = $this->activeRecordFactory->getObjectLoaded(
                'Listing\Product',
                $this->getListingProductId()
            );
        }

        return $this->listingProduct;
    }

    //########################################

    public function getListingProductId()
    {
        return (int)$this->getData('listing_product_id');
    }

    public function getComponent()
    {
        return $this->getData('component');
    }

    public function getType()
    {
        return $this->getData('type');
    }

    public function getInitiator()
    {
        return $this->getData('initiator');
    }

    public function getPriority()
    {
        return (int)$this->getData('priority');
    }

    public function getAdditionalData()
    {
        return $this->getSettings('additional_data');
    }

    public function getSkipUntil()
    {
        return $this->getData('skip_until');
    }

    //########################################
}
