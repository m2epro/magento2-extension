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
    private $processing = NULL;

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

    //####################################

    public function getAccountId()
    {
        return (int)$this->getData('account_id');
    }

    public function getMarketplaceId()
    {
        return (int)$this->getData('marketplace_id');
    }

    public function getProcessingId()
    {
        return (int)$this->getData('processing_id');
    }

    public function getRelatedId()
    {
        return (int)$this->getData('related_id');
    }

    public function getType()
    {
        return (int)$this->getData('type');
    }

    public function getPriority()
    {
        return (int)$this->getData('priority');
    }

    public function getRequestTimeOut()
    {
        return (int)$this->getData('request_timeout');
    }

    public function getRequestData()
    {
        return $this->getSettings('request_data');
    }

    public function getStartDate()
    {
        return (string)$this->getData('start_date');
    }

    //####################################
}