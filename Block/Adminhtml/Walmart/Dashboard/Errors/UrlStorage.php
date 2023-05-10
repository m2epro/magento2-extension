<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Dashboard\Errors;

class UrlStorage implements \Ess\M2ePro\Block\Adminhtml\Dashboard\Errors\UrlStorageInterface
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

    public function getUrlForToday(): string
    {
        $dateRange = $this->dateRangeFactory->createForToday();

        return $this->getUrl([
            'create_date[from]' => $dateRange->getDateStart()->format('Y-m-d H:i:s'),
            'create_date[to]' => $dateRange->getDateEnd()->format('Y-m-d H:i:s'),
            'type' => \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
        ]);
    }

    public function getUrlForYesterday(): string
    {
        $dateRange = $this->dateRangeFactory->createForYesterday();

        return $this->getUrl([
            'create_date[from]' => $dateRange->getDateStart()->format('Y-m-d H:i:s'),
            'create_date[to]' => $dateRange->getDateEnd()->format('Y-m-d H:i:s'),
            'type' => \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
        ]);
    }

    public function getUrlFor2DaysAgo(): string
    {
        $dateRange = $this->dateRangeFactory->createFor2DaysAgo();

        return $this->getUrl([
            'create_date[from]' => $dateRange->getDateStart()->format('Y-m-d H:i:s'),
            'create_date[to]' => $dateRange->getDateEnd()->format('Y-m-d H:i:s'),
            'type' => \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
        ]);
    }

    public function getUrlForTotal(): string
    {
        return $this->getUrl([
            'type' => \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
        ]);
    }

    private function getUrl(array $filterParams): string
    {
        /** @see \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid */
        return $this->url->getUrlWithFilter('*/walmart_log_listing_product/index', $filterParams);
    }
}
