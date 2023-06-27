<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Dashboard\Shipments;

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
            'status' => \Ess\M2ePro\Model\Amazon\Order::STATUS_UNSHIPPED,
            'shipping_date_to[to]' => \Ess\M2ePro\Helper\Date::convertToLocalFormat($currentDate),
            'shipping_date_to[locale]' => \Ess\M2ePro\Helper\Date::getLocaleResolver()->getLocale(),
        ]);
    }

    public function getUrlForShipByToday(): string
    {
        $dateRange = $this->dateRangeFactory->createForToday();
        $currentDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();

        return $this->getUrl([
            'status' => \Ess\M2ePro\Model\Amazon\Order::STATUS_UNSHIPPED,
            'shipping_date_to[from]' => \Ess\M2ePro\Helper\Date::convertToLocalFormat($currentDate),
            'shipping_date_to[to]' => \Ess\M2ePro\Helper\Date::convertToLocalFormat($dateRange->getDateEnd()),
            'shipping_date_to[locale]' => \Ess\M2ePro\Helper\Date::getLocaleResolver()->getLocale(),
        ]);
    }

    public function getUrlForShipByTomorrow(): string
    {
        $dateRange = $this->dateRangeFactory->createForTomorrow();

        return $this->getUrl([
            'status' => \Ess\M2ePro\Model\Amazon\Order::STATUS_UNSHIPPED,
            'shipping_date_to[from]' => \Ess\M2ePro\Helper\Date::convertToLocalFormat($dateRange->getDateStart()),
            'shipping_date_to[to]' => \Ess\M2ePro\Helper\Date::convertToLocalFormat($dateRange->getDateEnd()),
            'shipping_date_to[locale]' => \Ess\M2ePro\Helper\Date::getLocaleResolver()->getLocale(),
        ]);
    }

    public function getUrlForTwoAndMoreDays(): string
    {
        $dateRange = $this->dateRangeFactory->createForTwoAndMoreDays();

        return $this->getUrl([
            'status' => \Ess\M2ePro\Model\Amazon\Order::STATUS_UNSHIPPED,
            'shipping_date_to[from]' => \Ess\M2ePro\Helper\Date::convertToLocalFormat($dateRange->getDateStart()),
            'shipping_date_to[locale]' => \Ess\M2ePro\Helper\Date::getLocaleResolver()->getLocale(),
        ]);
    }

    private function getUrl(array $filterParams): string
    {
        /** @see \Ess\M2ePro\Block\Adminhtml\Amazon\Order\Grid */
        return $this->url->getUrlWithFilter('*/amazon_order/index', $filterParams);
    }
}
