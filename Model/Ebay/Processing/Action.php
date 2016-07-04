<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Processing;

class Action extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    const TYPE_LISTING_PRODUCT_LIST   = 0;
    const TYPE_LISTING_PRODUCT_REVISE = 1;
    const TYPE_LISTING_PRODUCT_RELIST = 2;
    const TYPE_LISTING_PRODUCT_STOP   = 3;

    //####################################

    /** @var \Ess\M2ePro\Model\Processing $processing */
    private $processing = null;

    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Processing\Action\Item\Collection $itemCollection */
    private $itemCollection = null;

    //####################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Processing\Action');
    }

    //####################################

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

        if (!is_null($this->processing)) {
            return $this->processing;
        }

        return $this->processing = $this->activeRecordFactory->getObjectLoaded('Processing', $this->getProcessingId());
    }

    //------------------------------------

    public function getItemCollection()
    {
        if (!$this->getId()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Instance must be loaded first.');
        }

        if (!is_null($this->itemCollection)) {
            return $this->itemCollection;
        }

        $this->itemCollection = $this->activeRecordFactory->getObject('Ebay\Processing\Action\Item')->getCollection();
        $this->itemCollection->setActionFilter($this);

        return $this->itemCollection;
    }

    //####################################

    public function getProcessingId()
    {
        return (int)$this->getData('processing_id');
    }

    public function getAccountId()
    {
        return (int)$this->getData('account_id');
    }

    public function getMarketplaceId()
    {
        return (int)$this->getData('marketplace_id');
    }

    public function getType()
    {
        return (int)$this->getData('type');
    }

    public function getRequestTimeOut()
    {
        return (int)$this->getData('request_timeout');
    }

    //####################################

    public function delete()
    {
        if (!parent::delete()) {
            return false;
        }

        $this->activeRecordFactory->getObject('Ebay\Processing\Action\Item')->getResource()->deleteByAction($this);

        return true;
    }

    //####################################
}