<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\Item\ProcessingRunner
 */
class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner\Single
{
    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    private $listingProduct = null;

    // ########################################

    public function processSuccess()
    {
        // listing product can be removed during processing action
        if ($this->getListingProduct()->getId() === null) {
            return true;
        }

        return parent::processSuccess();
    }

    public function processExpired()
    {
        // listing product can be removed during processing action
        if ($this->getListingProduct()->getId() === null) {
            return;
        }

        $this->getResponser()->failDetected($this->getExpiredErrorMessage());
    }

    public function complete()
    {
        // listing product can be removed during processing action
        if ($this->getListingProduct()->getId() === null) {
            $this->getProcessingObject()->delete();
            return;
        }

        parent::complete();
    }

    // ########################################

    protected function eventBefore()
    {
        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Ebay\Processing\Action $processingAction */
        $processingAction = $this->activeRecordFactory->getObject('Ebay_Processing_Action');
        $processingAction->setData([
            'account_id'      => $params['account_id'],
            'marketplace_id'  => $params['marketplace_id'],
            'processing_id'   => $this->getProcessingObject()->getId(),
            'related_id'      => $params['listing_product_id'],
            'type'            => $this->getProcessingActionType(),
            'priority'        => $params['priority'],
            'request_timeout' => $params['request_timeout'],
            'request_data'    => $this->getHelper('Data')->jsonEncode($params['request_data']),
            'start_date'      => $params['start_date'],
        ]);
        $processingAction->save();
    }

    protected function setLocks()
    {
        parent::setLocks();

        $params = $this->getParams();

        $this->getListingProduct()->addProcessingLock(null, $this->getProcessingObject()->getId());
        $this->getListingProduct()->addProcessingLock('in_action', $this->getProcessingObject()->getId());
        $this->getListingProduct()->addProcessingLock(
            $params['lock_identifier'].'_action',
            $this->getProcessingObject()->getId()
        );

        $this->getListingProduct()->getListing()->addProcessingLock(null, $this->getProcessingObject()->getId());
    }

    protected function unsetLocks()
    {
        if (!$this->getListingProduct()->getId()) {
            return;
        }

        parent::unsetLocks();

        $params = $this->getParams();

        $this->getListingProduct()->deleteProcessingLocks(null, $this->getProcessingObject()->getId());
        $this->getListingProduct()->deleteProcessingLocks('in_action', $this->getProcessingObject()->getId());
        $this->getListingProduct()->deleteProcessingLocks(
            $params['lock_identifier'].'_action',
            $this->getProcessingObject()->getId()
        );

        $this->getListingProduct()->getListing()->deleteProcessingLocks(null, $this->getProcessingObject()->getId());
    }

    // ########################################

    protected function getProcessingActionType()
    {
        $params = $this->getParams();

        switch ($params['action_type']) {
            case \Ess\M2ePro\Model\Listing\Product::ACTION_LIST:
                return \Ess\M2ePro\Model\Ebay\Processing\Action::TYPE_LISTING_PRODUCT_LIST;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE:
                return \Ess\M2ePro\Model\Ebay\Processing\Action::TYPE_LISTING_PRODUCT_REVISE;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST:
                return \Ess\M2ePro\Model\Ebay\Processing\Action::TYPE_LISTING_PRODUCT_RELIST;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:
                return \Ess\M2ePro\Model\Ebay\Processing\Action::TYPE_LISTING_PRODUCT_STOP;

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown action type.');
        }
    }

    protected function getListingProduct()
    {
        if (!empty($this->listingProduct)) {
            return $this->listingProduct;
        }

        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Ebay::NICK, 'Listing\Product')
            ->getCollection();
        $collection->addFieldToFilter('id', $params['listing_product_id']);

        return $this->listingProduct = $collection->getFirstItem();
    }

    // ########################################
}
