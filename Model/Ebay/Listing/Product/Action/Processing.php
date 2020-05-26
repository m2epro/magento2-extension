<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action;

use Ess\M2ePro\Model\ActiveRecord\AbstractModel;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Processing
 */
class Processing extends AbstractModel
{
    const TYPE_LIST   = 'list';
    const TYPE_RELIST = 'relist';
    const TYPE_REVISE = 'revise';
    const TYPE_STOP   = 'stop';

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct;

    /** @var \Ess\M2ePro\Model\Processing $processing */
    protected $processing;

    protected $ebayFactory;

    //####################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\Action\Processing');
    }

    //####################################

    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
        return $this;
    }

    public function getListingProduct()
    {
        if (!$this->getId()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Instance must be loaded first.');
        }

        if ($this->listingProduct !== null) {
            return $this->listingProduct;
        }

        return $this->listingProduct = $this->ebayFactory->getObjectLoaded(
            'Listing\Product',
            $this->getListingProductId()
        );
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

        if ($this->processing !== null) {
            return $this->processing;
        }

        return $this->processing = $this->activeRecordFactory->getObjectLoaded('Processing', $this->getProcessingId());
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

    public function getType()
    {
        return $this->getData('type');
    }

    public function getRequestTimeOut()
    {
        return (int)$this->getData('request_timeout');
    }

    public function getRequestData()
    {
        return $this->getSettings('request_data');
    }

    //####################################
}
