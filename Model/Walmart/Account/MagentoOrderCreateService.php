<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Account;

class MagentoOrderCreateService
{
    private \Ess\M2ePro\Model\Walmart\Order\Repository $walmartOrderRepository;
    private \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Creator $orderCreator;
    private \Ess\M2ePro\Helper\Module\Exception $exceptionHelper;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Order\Repository $walmartOrderRepository,
        \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Creator $orderCreator,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper
    ) {
        $this->walmartOrderRepository = $walmartOrderRepository;
        $this->orderCreator = $orderCreator;
        $this->exceptionHelper = $exceptionHelper;
    }

    public function createMagentoOrdersListingsByFromDate(int $accountId, \DateTime $fromDate): void
    {
        $this->createByFromDate($accountId, $fromDate);
    }

    public function createMagentoOrdersListingsOtherByFromDate(int $accountId, \DateTime $fromDate): void
    {
        $this->createByFromDate($accountId, $fromDate);
    }

    private function createByFromDate(int $accountId, \DateTime $fromDate): void
    {
        $fromDate->setTimezone(
            new \DateTimeZone(\Ess\M2ePro\Helper\Date::getTimezone()->getDefaultTimezone())
        );
        $toDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();

        $orders = $this->walmartOrderRepository->retrieveWithoutMagentoOrders(
            $accountId,
            $fromDate,
            $toDate
        );
        foreach ($orders as $order) {
            try {
                $this->orderCreator->createMagentoOrder($order);
            } catch (\Throwable $e) {
                $this->exceptionHelper->process($e);
            }
        }
    }
}
