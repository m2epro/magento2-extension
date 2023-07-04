<?php

namespace Ess\M2ePro\Api\Ebay;

interface OrderSearchCriteriaInterface
{
    public const DEFAULT_PAGE = 1;
    public const DEFAULT_PAGE_SIZE = 20;

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

    /**
     * @param int $pageSize
     *
     * @return void
     */
    public function setPageSize(int $pageSize): void;

    /**
     * @return int
     */
    public function getPageSize(): int;

    /**
     * @param string $marketplaceCode
     *
     * @return void
     */
    public function setMarketplaceCode(string $marketplaceCode): void;

    /**
     * @return string|null
     */
    public function getMarketplaceCode(): ?string;

    /**
     * @param int $accountId
     *
     * @return void
     */
    public function setAccountId(int $accountId): void;

    /**
     * @return int|null
     */
    public function getAccountId(): ?int;
}
