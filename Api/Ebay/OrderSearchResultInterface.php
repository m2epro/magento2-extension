<?php

namespace Ess\M2ePro\Api\Ebay;

interface OrderSearchResultInterface
{
    /**
     * @return \Ess\M2ePro\Api\Ebay\Data\OrderInterface[]
     */
    public function getItems(): array;

    /**
     * @param \Ess\M2ePro\Model\Ebay\Api\Data\Order[] $items
     *
     * @return void
     */
    public function setItems(array $items = []): void;

    /**
     * @return int
     */
    public function getPageSize(): int;

    /**
     * @param int $pageSize
     *
     * @return void
     */
    public function setPageSize(int $pageSize): void;

    /**
     * @param int $totalCount
     *
     * @return void
     */
    public function setTotalCount(int $totalCount): void;

    /**
     * @return int
     */
    public function getTotalCount(): int;

    /**
     * @param int $page
     *
     * @return void
     */
    public function setPage(int $page): void;

    /**
     * @return int
     */
    public function getPage(): int;
}
