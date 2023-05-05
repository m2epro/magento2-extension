<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Order\Cancellation\ByBuyer;

use Ess\M2ePro\Model\Ebay\Order as EbayOrder;

class MassManager
{
    /** @var Manager */
    private $cancellationManager;
    /** @var \Ess\M2ePro\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

    public function __construct(
        Manager $cancellationManager,
        \Ess\M2ePro\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    ) {
        $this->cancellationManager = $cancellationManager;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    public function approveCancellationRequests(array $ordersIds, int $initiator): MassManager\Result
    {
        return $this->processCancellationRequests(
            $ordersIds,
            Manager::ACTION_APPROVE,
            $initiator
        );
    }

    public function rejectCancellationRequests(array $ordersIds, int $initiator): MassManager\Result
    {
        return $this->processCancellationRequests(
            $ordersIds,
            Manager::ACTION_REJECT,
            $initiator
        );
    }

    private function processCancellationRequests(array $ordersIds, string $action, int $initiator): MassManager\Result
    {
        $success = $error = $notAllowed = 0;
        $collection = $this->orderCollectionFactory
            ->createWithEbayChildMode()
            ->appendFilterIds($ordersIds);

        /** @var \Ess\M2ePro\Model\Order $order */
        foreach ($collection->getItems() as $order) {
            $status = $order->getChildObject()->getBuyerCancellationStatus();
            if (
                $status !== EbayOrder::BUYER_CANCELLATION_STATUS_REQUESTED
                || !$order->getChildObject()->isBuyerCancellationPossible()
            ) {
                $notAllowed++;
                continue;
            }

            try {
                if ($action === Manager::ACTION_APPROVE) {
                    $result = $this->cancellationManager->approve(
                        $order,
                        $initiator
                    );
                } else {
                    $result = $this->cancellationManager->reject(
                        $order,
                        $initiator
                    );
                }

                if ($result) {
                    $success++;
                } else {
                    $error++;
                }
            } catch (\Throwable $exception) {
                $error++;
            }
        }

        return new MassManager\Result($success, $error, $notAllowed);
    }
}
