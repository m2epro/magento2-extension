<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Processing
 * @method \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Action\Processing getResource()
 */
class Processing extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    const TYPE_ADD    = 'add';
    const TYPE_UPDATE = 'update';
    const TYPE_DELETE = 'delete';

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct = null;

    /** @var \Ess\M2ePro\Model\Processing $processing */
    protected $processing = null;

    /** @var \Ess\M2ePro\Model\Request\Pending\Single $requestPendingSingle */
    protected $requestPendingSingle = null;

    //####################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Action\Processing');
    }

    //####################################

    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getListingProduct()
    {
        if (!$this->getId()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Instance must be loaded first.');
        }

        if ($this->listingProduct === null) {
            $this->listingProduct = $this->activeRecordFactory->getObjectLoaded(
                'Listing\Product',
                $this->getListingProductId()
            );
        }

        return $this->listingProduct;
    }

    // ---------------------------------------

    public function setProcessing(\Ess\M2ePro\Model\Processing $processing)
    {
        $this->processing = $processing;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Processing
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getProcessing()
    {
        if (!$this->getId()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Instance must be loaded first.');
        }

        if ($this->processing === null) {
            $this->processing = $this->activeRecordFactory->getObjectLoaded(
                'Processing',
                $this->getProcessingId()
            );
        }

        return $this->processing;
    }

    //------------------------------------

    public function setRequestPendingSingle(\Ess\M2ePro\Model\Request\Pending\Single $requestPendingSingle)
    {
        $this->requestPendingSingle = $requestPendingSingle;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Request\Pending\Single
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getRequestPendingSingle()
    {
        if (!$this->getId()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Instance must be loaded first.');
        }

        if (!$this->getRequestPendingSingleId()) {
            return null;
        }

        if ($this->requestPendingSingle !== null) {
            $this->requestPendingSingle = $this->activeRecordFactory->getObjectLoaded(
                'Request_Pending_Single',
                $this->getRequestPendingSingleId()
            );
        }

        return $this->requestPendingSingle;
    }

    //####################################

    public function getListingProductId()
    {
        return (int)$this->getData('listing_product_id');
    }

    public function getProcessingId()
    {
        return (int)$this->getData('processing_id');
    }

    public function getRequestPendingSingleId()
    {
        return (int)$this->getData('request_pending_single_id');
    }

    public function getType()
    {
        return $this->getData('type');
    }

    public function isPrepared()
    {
        return (bool)$this->getData('is_prepared');
    }

    public function getGroupHash()
    {
        return $this->getData('group_hash');
    }

    public function getRequestData()
    {
        return $this->getSettings('request_data');
    }

    //####################################
}
