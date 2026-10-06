<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\BillingAddress;

class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\BillingAddress\ItemsResponser
{
    private \Ess\M2ePro\Model\Synchronization\Log $synchronizationLog;

    private \Ess\M2ePro\Model\Amazon\Order\Repository $amazonOrderRepository;
    private \Ess\M2ePro\Model\Amazon\Order\AddressFactory $addressFactory;
    private \Ess\M2ePro\Model\Cron\Task\Amazon\Order\CreatorFactory $creatorFactory;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Order\Repository $amazonOrderRepository,
        \Ess\M2ePro\Model\Amazon\Order\AddressFactory $addressFactory,
        \Ess\M2ePro\Model\Cron\Task\Amazon\Order\CreatorFactory $creatorFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        array $params = []
    ) {
        parent::__construct(
            $response,
            $helperFactory,
            $modelFactory,
            $amazonFactory,
            $walmartFactory,
            $ebayFactory,
            $activeRecordFactory,
            $params
        );
        $this->amazonOrderRepository = $amazonOrderRepository;
        $this->addressFactory = $addressFactory;
        $this->creatorFactory = $creatorFactory;
    }

    protected function processResponseMessages(array $messages = []): void
    {
        parent::processResponseMessages();

        foreach ($this->getResponse()->getMessages()->getEntities() as $message) {
            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            $logType = $message->isError()
                ? \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                : \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;

            $this
                ->getSynchronizationLog()
                ->addMessage(
                    (string)__('The billing address could not be retrieved. Reason: %reason', [
                        'reason' => $message->getText(),
                    ]),
                    $logType
                );
        }
    }

    protected function isNeedProcessResponse(): bool
    {
        if (!parent::isNeedProcessResponse()) {
            return false;
        }

        if ($this->getResponse()->getMessages()->hasErrorEntities()) {
            return false;
        }

        return true;
    }

    protected function processResponseData(): void
    {
        $responseItems = $this->getPreparedResponseData();

        $amazonOrderIds = array_map(function ($responseItem) {
            return $responseItem->amazonOrderId;
        }, $responseItems);

        $orders = $this->amazonOrderRepository->findByAmazonOrderIds($amazonOrderIds);
        foreach ($responseItems as $responseItem) {
            $order = $orders[$responseItem->amazonOrderId] ?? null;
            if ($order === null) {
                continue;
            }

            $this->setBillingAddress($order, $responseItem);
            $this->createMagentoOrder($order);
        }
    }

    private function setBillingAddress(
        \Ess\M2ePro\Model\Order $order,
        \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\BillingAddress\ResponseItem $responseItem
    ): void {
        $billingAddress = $this->addressFactory->createBillingAddress($order, [
            \Ess\M2ePro\Model\Amazon\Order\BillingAddress::DATA_KEY_COUNTRY_CODE => $responseItem->country,
            \Ess\M2ePro\Model\Amazon\Order\BillingAddress::DATA_KEY_STATE => $responseItem->state,
            \Ess\M2ePro\Model\Amazon\Order\BillingAddress::DATA_KEY_CITY => $responseItem->city,
            \Ess\M2ePro\Model\Amazon\Order\BillingAddress::DATA_KEY_POSTAL_CODE => $responseItem->postalCode,
            \Ess\M2ePro\Model\Amazon\Order\BillingAddress::DATA_KEY_STREET => $responseItem->addressLines,
        ]);

        /** @var \Ess\M2ePro\Model\Amazon\Order $amazonOrder */
        $amazonOrder = $order->getChildObject();
        $amazonOrder->setBillingAddress($billingAddress);

        $amazonOrder->save();
    }

    private function createMagentoOrder(\Ess\M2ePro\Model\Order $order)
    {
        $orderCreator = $this->creatorFactory->create();
        $orderCreator->createMagentoOrder($order);
    }

    private function getSynchronizationLog(): \Ess\M2ePro\Model\Synchronization\Log
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->synchronizationLog)) {
            return $this->synchronizationLog;
        }

        /** @var \Ess\M2ePro\Model\Synchronization\Log $synchronizationLog */
        $synchronizationLog = $this->activeRecordFactory->getObject('Synchronization_Log');
        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_ORDERS);

        $this->synchronizationLog = $synchronizationLog;

        return $this->synchronizationLog;
    }
}
