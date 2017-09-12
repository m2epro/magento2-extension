<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item;

class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner\Single
{
    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    private $listingProduct = NULL;

    // ########################################

    public function processSuccess()
    {
        // listing product can be removed during processing action
        if (is_null($this->getListingProduct()->getId())) {
            return true;
        }

        return parent::processSuccess();
    }

    public function processExpired()
    {
        // listing product can be removed during processing action
        if (is_null($this->getListingProduct()->getId())) {
            return;
        }

        $this->getResponser()->failDetected($this->getExpiredErrorMessage());
    }

    public function complete()
    {
        // listing product can be removed during processing action
        if (is_null($this->getListingProduct()->getId())) {
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
        $processingAction = $this->activeRecordFactory->getObject('Ebay\Processing\Action');
        $processingAction->setData(array(
            'account_id'      => $params['account_id'],
            'marketplace_id'  => $params['marketplace_id'],
            'processing_id'   => $this->getProcessingObject()->getId(),
            'related_id'      => $params['listing_product_id'],
            'type'            => $this->getProcessingActionType(),
            'priority'        => $params['priority'],
            'request_timeout' => $params['request_timeout'],
            'request_data'    => $this->getHelper('Data')->jsonEncode($params['request_data']),
            'start_date'      => $params['start_date'],
        ));
        $processingAction->save();
    }

    protected function setLocks()
    {
        parent::setLocks();

        $params = $this->getParams();

        $this->getListingProduct()->addProcessingLock(NULL, $this->getProcessingObject()->getId());
        $this->getListingProduct()->addProcessingLock('in_action', $this->getProcessingObject()->getId());
        $this->getListingProduct()->addProcessingLock(
            $params['lock_identifier'].'_action', $this->getProcessingObject()->getId()
        );

        $this->getListingProduct()->getListing()->addProcessingLock(NULL, $this->getProcessingObject()->getId());
    }

    protected function unsetLocks()
    {
        if (!$this->getListingProduct()->getId()) {
            return;
        }

        parent::unsetLocks();

        $params = $this->getParams();

        $this->getListingProduct()->deleteProcessingLocks(NULL, $this->getProcessingObject()->getId());
        $this->getListingProduct()->deleteProcessingLocks('in_action', $this->getProcessingObject()->getId());
        $this->getListingProduct()->deleteProcessingLocks(
            $params['lock_identifier'].'_action', $this->getProcessingObject()->getId()
        );

        $this->getListingProduct()->getListing()->deleteProcessingLocks(NULL, $this->getProcessingObject()->getId());
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