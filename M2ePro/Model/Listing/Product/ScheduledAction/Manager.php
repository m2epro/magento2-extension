<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product\ScheduledAction;

/**
 * Class \Ess\M2ePro\Model\Listing\Product\ScheduledAction\Manager
 */
class Manager extends \Ess\M2ePro\Model\AbstractModel
{
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function addAction(\Ess\M2ePro\Model\Listing\Product\ScheduledAction $scheduledAction)
    {
        $scheduledAction->save();
    }

    public function updateAction(\Ess\M2ePro\Model\Listing\Product\ScheduledAction $scheduledAction)
    {
        $scheduledActionCollection = $this->activeRecordFactory
            ->getObject('Listing_Product_ScheduledAction')->getCollection();
        $scheduledActionCollection->addFieldToFilter('listing_product_id', $scheduledAction->getListingProductId());

        /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction $existedScheduledAction */
        $existedScheduledAction = $scheduledActionCollection->getFirstItem();
        $existedScheduledAction->addData($scheduledAction->getData());
        $existedScheduledAction->save();
    }

    public function deleteAction(\Ess\M2ePro\Model\Listing\Product\ScheduledAction $scheduledAction)
    {
        if (!$scheduledAction->getId()) {
            return;
        }

        $scheduledAction->delete();
    }

    //########################################
}
