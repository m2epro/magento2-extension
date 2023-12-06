<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Order\ReturnRequest\Decide;

use Ess\M2ePro\Model\Ebay\Order as EbayOrder;

class MassManager
{
    /** @var Manager */
    private $returnManager;
    /** @var \Ess\M2ePro\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;

    public function __construct(
        Manager $returnManager,
        \Ess\M2ePro\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    ) {
        $this->returnManager = $returnManager;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    public function approveReturnRequests(array $ordersIds, int $initiator): MassManager\Result
    {
        return $this->processReturnRequests(
            $ordersIds,
            Manager::DECIDE_APPROVE,
            $initiator
        );
    }

    public function declineReturnRequests(array $ordersIds, int $initiator): MassManager\Result
    {
        return $this->processReturnRequests(
            $ordersIds,
            Manager::DECIDE_DECLINE,
            $initiator
        );
    }

    private function processReturnRequests(array $ordersIds, string $decide, int $initiator): MassManager\Result
    {
        $success = $error = $notAllowed = 0;
        $collection = $this->orderCollectionFactory
            ->createWithEbayChildMode()
            ->appendFilterIds($ordersIds);

        /** @var \Ess\M2ePro\Model\Order $order */
        foreach ($collection->getItems() as $order) {
            if (!$order->getChildObject()->isBuyerReturnRequested()) {
                $notAllowed++;
                continue;
            }

            try {
                if ($decide === Manager::DECIDE_APPROVE) {
                    $result = $this->returnManager->approve(
                        $order,
                        $initiator
                    );
                } else {
                    $result = $this->returnManager->decline(
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
