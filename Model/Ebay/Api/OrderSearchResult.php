<?php

namespace Ess\M2ePro\Model\Ebay\Api;

class OrderSearchResult implements \Ess\M2ePro\Api\Ebay\OrderSearchResultInterface
{
    /** @var \Ess\M2ePro\Model\Ebay\Api\Data\Order[] */
    private $items;
    /** @var int */
    private $pageSize;
    /** @var int */
    private $totalCount;
    /** @var int */
    private $page;

    public function setItems(array $items = []): void
    {
        $this->items = $items;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function setPageSize(int $pageSize): void
    {
        $this->pageSize = $pageSize;
    }

    public function setTotalCount(int $totalCount): void
    {
        $this->totalCount = $totalCount;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function getPage(): int
    {
        return $this->page;
    }
}
