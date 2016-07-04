<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\Single;

class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner\Single
{
    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    private $listingProduct = array();

    // ########################################

    protected function eventBefore()
    {
        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Ebay\Processing\Action $processingAction */
        $processingAction = $this->activeRecordFactory->getObject('Ebay\Processing\Action');
        $processingAction->setData(array(
            'processing_id'   => $this->getProcessingObject()->getId(),
            'account_id'      => $params['account_id'],
            'marketplace_id'  => $params['marketplace_id'],
            'type'            => $this->getProcessingActionType(),
            'request_timeout' => $params['request_timeout'],
        ));

        $processingAction->save();

        /** @var \Ess\M2ePro\Model\Ebay\Processing\Action\Item $processingActionItem */
        $processingActionItem = $this->activeRecordFactory->getObject('Ebay\Processing\Action\Item');
        $processingActionItem->setData(array(
            'action_id'  => $processingAction->getId(),
            'related_id' => $params['listing_product_id'],
            'input_data' => json_encode($params['request_data']),
        ));

        $processingActionItem->save();
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

        return $this->listingProduct = $this->parentFactory->getObjectLoaded(
            \Ess\M2ePro\Helper\Component\Ebay::NICK, 'Listing\Product', $params['listing_product_id']
        );
    }

    // ########################################
}