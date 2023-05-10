<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Dashboard\Shipments;

class UrlStorage implements \Ess\M2ePro\Block\Adminhtml\Dashboard\Shipments\UrlStorageInterface
{
    /** @var \Ess\M2ePro\Helper\Url */
    private $url;
    /** @var \Ess\M2ePro\Model\Dashboard\Date\DateRangeFactory */
    private $dateRangeFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Url $url,
        \Ess\M2ePro\Model\Dashboard\Date\DateRangeFactory $dateRangeFactory
    ) {
        $this->url = $url;
        $this->dateRangeFactory = $dateRangeFactory;
    }

    public function getUrlForLateShipments(): string
    {
        $currentDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();

        return $this->getUrl([
            'status' => \Ess\M2ePro\Model\Walmart\Order::STATUS_UNSHIPPED,
            'shipping_date_to[from]' => $currentDate->format('Y-m-d H:i:s'),
        ]);
    }

    public function getUrlForToday(): string
    {
        $dateRange = $this->dateRangeFactory->createForToday();

        return $this->getUrl([
            'status' => \Ess\M2ePro\Model\Walmart\Order::STATUS_UNSHIPPED,
            'shipping_date_to[from]' => $dateRange->getDateStart()->format('Y-m-d H:i:s'),
            'shipping_date_to[to]' => $dateRange->getDateEnd()->format('Y-m-d H:i:s'),
        ]);
    }

    public function getUrlForTotal(): string
    {
        return $this->getUrl(['status' => \Ess\M2ePro\Model\Walmart\Order::STATUS_UNSHIPPED]);
    }

    public function getUrlForOver2Days(): string
    {
        $currentDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $dateIn2Days = $currentDate->modify('+2 days');

        return $this->getUrl([
            'status' => \Ess\M2ePro\Model\Walmart\Order::STATUS_UNSHIPPED,
            'shipping_date_to[from]' => $dateIn2Days->format('Y-m-d H:i:s'),
        ]);
    }

    private function getUrl(array $filterParams): string
    {
        /** @see \Ess\M2ePro\Block\Adminhtml\Walmart\Order\Grid */
        return $this->url->getUrlWithFilter('*/walmart_order/index', $filterParams);
    }
}
