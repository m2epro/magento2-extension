<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Listing\Product\ScheduledAction
 */
class ScheduledAction extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Listing\Product */
    protected $listingProduct = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction');
    }

    //########################################

    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
    }

    public function getListingProduct()
    {
        if ($this->getId() === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Model must be loaded.');
        }

        if ($this->listingProduct !== null) {
            return $this->listingProduct;
        }

        $this->listingProduct = $this->activeRecordFactory->getObjectLoaded(
            'Listing\Product',
            $this->getListingProductId()
        );

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

    public function getActionType()
    {
        return (int)$this->getData('action_type');
    }

    public function isActionTypeList()
    {
        return $this->getActionType() == \Ess\M2ePro\Model\Listing\Product::ACTION_LIST;
    }

    public function isActionTypeRelist()
    {
        return $this->getActionType() == \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST;
    }

    public function isActionTypeRevise()
    {
        return $this->getActionType() == \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE;
    }

    public function isActionTypeStop()
    {
        return $this->getActionType() == \Ess\M2ePro\Model\Listing\Product::ACTION_STOP;
    }

    public function isActionTypeDelete()
    {
        return $this->getActionType() == \Ess\M2ePro\Model\Listing\Product::ACTION_DELETE;
    }

    public function isForce()
    {
        return (bool)$this->getData('is_force');
    }

    public function getTag()
    {
        return $this->getData('tag');
    }

    public function getAdditionalData()
    {
        return $this->getSettings('additional_data');
    }

    //########################################
}
