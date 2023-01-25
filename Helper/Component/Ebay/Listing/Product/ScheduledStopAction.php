<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Ebay\Listing\Product;

use Ess\M2ePro\Model\Ebay\Listing\Product\ScheduledStopAction\Factory as ScheduledStopActionFactory;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\ScheduledStopAction\Collection\Factory
    as ScheduledStopActionCollectionFactory;

class ScheduledStopAction
{
    private const SCHEDULED_STOP_ACTION_EXPIRATION_DAYS = 30;

    /** @var ScheduledStopActionFactory */
    private $scheduledStopActionFactory;
    /** @var ScheduledStopActionCollectionFactory */
    private $scheduledStopActionCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\ScheduledStopAction */
    private $scheduledStopActionResource;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    public function __construct(
        ScheduledStopActionFactory $scheduledStopActionFactory,
        ScheduledStopActionCollectionFactory $scheduledStopActionCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\ScheduledStopAction $scheduledStopActionResource,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->scheduledStopActionFactory = $scheduledStopActionFactory;
        $this->scheduledStopActionCollectionFactory = $scheduledStopActionCollectionFactory;
        $this->scheduledStopActionResource = $scheduledStopActionResource;
        $this->resourceConnection = $resourceConnection;
    }

    public function isStopActionScheduled(int $listingProductId): bool
    {
        $collection = $this->scheduledStopActionCollectionFactory
            ->create()
            ->appendFilterNotProcessed()
            ->appendFilterListingProductId($listingProductId);

        return (bool)$collection->getSize();
    }

    /**
     * @param int $listingProductId
     *
     * @return void
     * @throws \Exception
     */
    public function scheduleStopAction(int $listingProductId): void
    {
        $this->scheduledStopActionFactory->create()
            ->setListingProductId($listingProductId)
            ->setCreateDate(\Ess\M2ePro\Helper\Date::createCurrentGmt())
            ->save();
    }

    /**
     * @param int $listingProductId
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function removeScheduledStopAction(int $listingProductId): void
    {
        $this->resourceConnection->getConnection()->delete(
            $this->scheduledStopActionResource->getMainTable(),
            'process_date IS NULL AND listing_product_id = ' . $listingProductId
        );
    }

    /**
     * @param int $listingProductId
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function markScheduledStopActionAsProcessed(int $listingProductId): void
    {
        $date = \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s');

        $this->resourceConnection->getConnection()->update(
            $this->scheduledStopActionResource->getMainTable(),
            ['process_date' => $date],
            'process_date IS NULL AND listing_product_id = ' . $listingProductId
        );
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function removeOldScheduledStopActionData(): void
    {
        $expirationDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $expirationDate->modify('-' . self::SCHEDULED_STOP_ACTION_EXPIRATION_DAYS . ' days');

        $collection = $this->scheduledStopActionCollectionFactory->create();
        $collection->deleteOldScheduledStopActions($expirationDate);
    }
}
