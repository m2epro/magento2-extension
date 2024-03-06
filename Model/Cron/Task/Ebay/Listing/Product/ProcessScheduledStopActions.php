<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product;

use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\ScheduledStopAction\Collection\Factory
    as ScheduledStopActionCollectionFactory;

class ProcessScheduledStopActions extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'ebay/listing/product/process_scheduled_stop_actions';

    private const TIME_SHIFT_TO_STOP_LISTING_PRODUCTS = 1800; // 30 minutes
    private const MAX_ITEMS_PER_RUN = 2000;
    private const INSTRUCTION_INITIATOR = 'ebay_listing_product_scheduled_stop_action';

    /** @var int (in seconds) */
    protected $interval = 300;
    /** @var int */
    private $logsActionId;

    /** @var ScheduledStopActionCollectionFactory */
    private $scheduledStopActionCollectionFactory;
    /** @var \Ess\M2ePro\Helper\Component\Ebay */
    private $ebayHelper;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Listing\Product\ScheduledStopAction */
    private $scheduledStopActionHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection\Factory */
    private $listingProductCollectionFactory;
    /** @var \Ess\M2ePro\Model\Listing\Product\Instruction\Factory */
    private $listingProductInstructionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Log */
    private $listingLogResource;
    /** @var \Ess\M2ePro\Model\Listing\Log */
    private $listingLog;

    public function __construct(
        ScheduledStopActionCollectionFactory $scheduledStopActionCollectionFactory,
        \Ess\M2ePro\Helper\Component\Ebay $ebayHelper,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Ess\M2ePro\Helper\Component\Ebay\Listing\Product\ScheduledStopAction $scheduledStopActionHelper,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection\Factory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\Listing\Product\Instruction\Factory $listingProductInstructionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Log $listingLogResource,
        \Ess\M2ePro\Model\Listing\Log\Factory $listingLogFactory,
        \Ess\M2ePro\Model\Cron\Manager $cronManager,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct(
            $cronManager,
            $helperData,
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );

        $this->scheduledStopActionCollectionFactory = $scheduledStopActionCollectionFactory;
        $this->ebayHelper = $ebayHelper;
        $this->translationHelper = $translationHelper;
        $this->scheduledStopActionHelper = $scheduledStopActionHelper;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->listingProductInstructionFactory = $listingProductInstructionFactory;
        $this->listingLogResource = $listingLogResource;

        $this->listingLog = $listingLogFactory->create();
        $this->listingLog->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
    protected function performActions()
    {
        $this->scheduledStopActionHelper->removeOldScheduledStopActionData();

        $date = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $date->modify('-' . self::TIME_SHIFT_TO_STOP_LISTING_PRODUCTS . ' seconds');

        $scheduledActionCollection = $this->scheduledStopActionCollectionFactory->create()
            ->appendFilterCreateDateLessThan($date)
            ->appendFilterNotProcessed()
            ->setLimit(self::MAX_ITEMS_PER_RUN);

        $listingProductIds = [];
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\ScheduledStopAction $item */
        foreach ($scheduledActionCollection->getItems() as $item) {
            $listingProductIds[] = $item->getListingProductId();
        }

        if (empty($listingProductIds)) {
            return;
        }

        $statusChangedTo = $this->ebayHelper
            ->getHumanTitleByListingProductStatus(\Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE);

        $listingProductCollection = $this->listingProductCollectionFactory->create();
        $listingProductCollection->addFieldToFilter('id', ['in' => $listingProductIds]);
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        foreach ($listingProductCollection->getItems() as $listingProduct) {
            if ($listingProduct->isInactive()) {
                $this->scheduledStopActionHelper->removeScheduledStopAction((int)$listingProduct->getId());
                continue;
            }

            $statusChangedFrom = $this->ebayHelper
                ->getHumanTitleByListingProductStatus($listingProduct->getStatus());

            $listingProduct->addData([
                'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE,
                'status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT,
            ])->save();

            if (!empty($statusChangedFrom)) {
                $this->addLog(
                    $listingProduct,
                    $this->translationHelper->__(
                        'Item Status was changed from "%from%" to "%to%" .',
                        $statusChangedFrom,
                        $statusChangedTo
                    )
                );
            }

            $this->addInstruction($listingProduct);

            $this->scheduledStopActionHelper->markScheduledStopActionAsProcessed((int)$listingProduct->getId());
        }
    }

    private function addLog(\Ess\M2ePro\Model\Listing\Product $listingProduct, string $message): void
    {
        $this->listingLog->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            $this->getLogsActionId(),
            \Ess\M2ePro\Model\Listing\Log::ACTION_CHANNEL_CHANGE,
            $message,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS
        );
    }

    private function getLogsActionId(): int
    {
        if ($this->logsActionId === null) {
            $this->logsActionId = $this->listingLogResource->getNextActionId();
        }

        return $this->logsActionId;
    }

    private function addInstruction(\Ess\M2ePro\Model\Listing\Product $listingProduct): void
    {
        $this->listingProductInstructionFactory->create()
            ->setData(
                [
                    'listing_product_id' => $listingProduct->getId(),
                    'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
                    'type' => \Ess\M2ePro\Model\Ebay\Listing\Product::INSTRUCTION_TYPE_CHANNEL_STATUS_CHANGED,
                    'initiator' => self::INSTRUCTION_INITIATOR,
                    'priority' => 80,
                ]
            )
            ->save();
    }
}
