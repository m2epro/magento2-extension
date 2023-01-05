<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product\ScheduledAction;

class Manager
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\CollectionFactory */
    private $collectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    // ----------------------------------------

    public function addAction(\Ess\M2ePro\Model\Listing\Product\ScheduledAction $scheduledAction): void
    {
        $scheduledAction->save();
    }

    public function updateAction(\Ess\M2ePro\Model\Listing\Product\ScheduledAction $scheduledAction): void
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $scheduledActionCollection */
        $scheduledActionCollection = $this->collectionFactory->create();
        $scheduledActionCollection->addFieldToFilter('listing_product_id', $scheduledAction->getListingProductId());

        /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction $existedScheduledAction */
        $existedScheduledAction = $scheduledActionCollection->getFirstItem();
        $existedScheduledAction->addData($scheduledAction->getData());
        $existedScheduledAction->save();
    }

    public function deleteAction(\Ess\M2ePro\Model\Listing\Product\ScheduledAction $scheduledAction): void
    {
        if (!$scheduledAction->getId()) {
            return;
        }

        $scheduledAction->delete();
    }
}
