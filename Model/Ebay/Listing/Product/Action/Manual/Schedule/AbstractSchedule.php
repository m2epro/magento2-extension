<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Schedule;

use \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Result;

abstract class AbstractSchedule extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\AbstractManual
{
    /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledActionFactory */
    private $scheduledActionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\CollectionFactory */
    private $scheduledActionCollectionFactory;
    /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction\Manager */
    private $scheduledActionManager;

    public function __construct(
        \Ess\M2ePro\Model\Listing\Product\ScheduledActionFactory $scheduledActionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\CollectionFactory $scheduledActionCollectionFactory,
        \Ess\M2ePro\Model\Listing\Product\ScheduledAction\Manager $scheduledActionManager,
        \Ess\M2ePro\Model\Listing\Product\LockManagerFactory $lockManagerFactory
    ) {
        parent::__construct($lockManagerFactory);
        $this->scheduledActionFactory = $scheduledActionFactory;
        $this->scheduledActionCollectionFactory = $scheduledActionCollectionFactory;
        $this->scheduledActionManager = $scheduledActionManager;
    }

    protected function createScheduleAction(
        \Ess\M2ePro\Model\Listing\Product $product,
        array $params
    ): ?\Ess\M2ePro\Model\Listing\Product\ScheduledAction {
        $params['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER;

        $data = [
            'listing_product_id' => $product->getId(),
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'action_type' => $this->getAction(),
            'is_force' => true,
            'tag' => null,
            'additional_data' => \Ess\M2ePro\Helper\Json::encode(
                [
                    'params' => $params,
                ]
            ),
        ];

        return $this->createAction($data);
    }

    protected function processListingsProducts(array $listingsProducts, array $params): Result
    {
        $listingsProductsIds = [];
        foreach ($listingsProducts as $listingProduct) {
            $listingsProductsIds[] = $listingProduct->getId();
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $existedScheduled */
        $existedScheduled = $this->scheduledActionCollectionFactory->create();
        $existedScheduled->addFieldToFilter('listing_product_id', $listingsProductsIds);

        foreach ($listingsProducts as $listingProduct) {
            $action = $this->createScheduleAction($listingProduct, $params);
            if ($action === null) {
                continue;
            }

            if ($existedScheduled->getItemByColumnValue('listing_product_id', $listingProduct->getId())) {
                $this->scheduledActionManager->updateAction($action);
            } else {
                $this->scheduledActionManager->addAction($action);
            }
        }

        return Result::createSuccess($this->getLogActionId());
    }

    // ----------------------------------------

    protected function createAction(array $data): \Ess\M2ePro\Model\Listing\Product\ScheduledAction
    {
        $action = $this->scheduledActionFactory->create();
        $action->setData($data);

        return $action;
    }
}
