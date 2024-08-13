<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Account;

class MagentoOrderCreateService
{
    private \Ess\M2ePro\Model\Ebay\Order\Repository $ebayOrderRepository;
    private \Ess\M2ePro\Model\Cron\Task\Ebay\Order\Creator $orderCreator;
    private \Ess\M2ePro\Helper\Module\Exception $exceptionHelper;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Order\Repository $ebayOrderRepository,
        \Ess\M2ePro\Model\Cron\Task\Ebay\Order\Creator $orderCreator,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper
    ) {
        $this->ebayOrderRepository = $ebayOrderRepository;
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

        $orders = $this->ebayOrderRepository->retrieveWithoutMagentoOrders(
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
