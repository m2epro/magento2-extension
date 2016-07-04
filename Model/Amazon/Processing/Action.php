<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Processing;

class Action extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    const TYPE_PRODUCT_ADD    = 0;
    const TYPE_PRODUCT_UPDATE = 1;
    const TYPE_PRODUCT_DELETE = 2;

    const TYPE_ORDER_UPDATE   = 3;
    const TYPE_ORDER_CANCEL   = 4;
    const TYPE_ORDER_REFUND   = 5;

    //####################################

    /** @var \Ess\M2ePro\Model\Processing $processing */
    private $processing = null;

    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Processing\Action\Item\Collection $itemCollection */
    private $itemCollection = null;

    //####################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Processing\Action');
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

        return $this->processing = $this->activeRecordFactory->getObjectLoaded(
            'Processing', $this->getProcessingId(), NULL, false
        );
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

        $this->itemCollection = $this->activeRecordFactory->getObject('Amazon\Processing\Action\Item')->getCollection();
        $this->itemCollection->setActionFilter($this);

        return $this->itemCollection;
    }

    //####################################

    public function getAccountId()
    {
        return (int)$this->getData('account_id');
    }

    public function getProcessingId()
    {
        return (int)$this->getData('processing_id');
    }

    public function getType()
    {
        return (int)$this->getData('type');
    }

    //####################################

    public function delete()
    {
        if (!parent::delete()) {
            return false;
        }

        $this->activeRecordFactory->getObject('Amazon\Processing\Action\Item')->getResource()->deleteByAction($this);

        return true;
    }

    //####################################
}