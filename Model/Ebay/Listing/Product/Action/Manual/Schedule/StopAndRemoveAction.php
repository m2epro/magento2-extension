<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Schedule;

class StopAndRemoveAction extends AbstractSchedule
{
    /** @var \Ess\M2ePro\Model\Listing\Product\RemoveHandlerFactory */
    private $removeHandlerFactory;

    public function __construct(
        \Ess\M2ePro\Model\Listing\Product\RemoveHandlerFactory $removeHandlerFactory,
        \Ess\M2ePro\Model\Listing\Product\ScheduledActionFactory $scheduledActionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\CollectionFactory $scheduledActionCollectionFactory,
        \Ess\M2ePro\Model\Listing\Product\ScheduledAction\Manager $scheduledActionManager,
        \Ess\M2ePro\Model\Listing\Product\LockManagerFactory $lockManagerFactory
    ) {
        parent::__construct(
            $scheduledActionFactory,
            $scheduledActionCollectionFactory,
            $scheduledActionManager,
            $lockManagerFactory
        );
        $this->removeHandlerFactory = $removeHandlerFactory;
    }

    protected function getAction(): int
    {
        return \Ess\M2ePro\Model\Listing\Product::ACTION_STOP;
    }

    protected function prepareOrFilterProducts(array $listingsProducts): array
    {
        $result = [];
        foreach ($listingsProducts as $listingProduct) {
            if ($listingProduct->isStoppable()) {
                $result[] = $listingProduct;

                continue;
            }

            $this->removeHandlerFactory->create($listingProduct)
                                       ->process();
        }

        return $result;
    }
}
