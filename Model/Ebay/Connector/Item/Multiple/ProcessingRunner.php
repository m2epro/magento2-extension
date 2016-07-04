<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\Multiple;

class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner\Single
{
    /** @var \Ess\M2ePro\Model\Listing\Product[] $listingsProducts */
    private $listingsProducts = array();

    // ########################################

    function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($parentFactory, $activeRecordFactory, $helperFactory, $modelFactory);
    }

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

        foreach ($params['request_data']['items'] as $listingProductId => $productData) {
            /** @var \Ess\M2ePro\Model\Ebay\Processing\Action\Item $processingActionItem */
            $processingActionItem = $this->activeRecordFactory->getObject('Ebay\Processing\Action\Item');
            $processingActionItem->setData(array(
                'action_id'  => $processingAction->getId(),
                'related_id' => $listingProductId,
                'input_data' => json_encode($productData),
            ));

            $processingActionItem->save();
        }
    }

    protected function setLocks()
    {
        parent::setLocks();

        $params = $this->getParams();

        $alreadyLockedListings = array();
        foreach ($this->getListingsProducts() as $listingProduct) {

            /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */

            $listingProduct->addProcessingLock(NULL, $this->getProcessingObject()->getId());
            $listingProduct->addProcessingLock('in_action', $this->getProcessingObject()->getId());
            $listingProduct->addProcessingLock(
                $params['lock_identifier'].'_action', $this->getProcessingObject()->getId()
            );

            if (isset($alreadyLockedListings[$listingProduct->getListingId()])) {
                continue;
            }

            $listingProduct->getListing()->addProcessingLock(NULL, $this->getProcessingObject()->getId());

            $alreadyLockedListings[$listingProduct->getListingId()] = true;
        }
    }

    protected function unsetLocks()
    {
        parent::unsetLocks();

        $params = $this->getParams();

        $alreadyUnlockedListings = array();
        foreach ($this->getListingsProducts() as $listingProduct) {

            $listingProduct->deleteProcessingLocks(NULL, $this->getProcessingObject()->getId());
            $listingProduct->deleteProcessingLocks('in_action', $this->getProcessingObject()->getId());
            $listingProduct->deleteProcessingLocks(
                $params['lock_identifier'].'_action', $this->getProcessingObject()->getId()
            );

            if (isset($alreadyUnlockedListings[$listingProduct->getListingId()])) {
                continue;
            }

            $listingProduct->getListing()->deleteProcessingLocks(NULL, $this->getProcessingObject()->getId());

            $alreadyUnlockedListings[$listingProduct->getListingId()] = true;
        }
    }

    // ########################################

    protected function getProcessingActionType()
    {
        $params = $this->getParams();

        switch ($params['action_type']) {
            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:
                return \Ess\M2ePro\Model\Ebay\Processing\Action::TYPE_LISTING_PRODUCT_STOP;

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown action type.');
        }
    }

    protected function getListingsProducts()
    {
        if (!empty($this->listingsProducts)) {
            return $this->listingsProducts;
        }

        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->ebayFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('id', array('in' => $params['listing_product_ids']));

        return $this->listingsProducts = $collection->getItems();
    }

    // ########################################
}