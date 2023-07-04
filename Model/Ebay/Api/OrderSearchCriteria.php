<?php

namespace Ess\M2ePro\Model\Ebay\Api;

class OrderSearchCriteria implements \Ess\M2ePro\Api\Ebay\OrderSearchCriteriaInterface
{
    /** @var int */
    private $page;
    /** @var int */
    private $pageSize;
    /** @var string */
    private $marketPlaceCode;
    /** @var int */
    private $accountId;

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function getPage(): int
    {
        return $this->page ?? self::DEFAULT_PAGE;
    }

    public function setPageSize(int $pageSize): void
    {
        $this->pageSize = $pageSize;
    }

    public function getPageSize(): int
    {
        return $this->pageSize ?? self::DEFAULT_PAGE_SIZE;
    }

    public function setMarketplaceCode(string $marketplaceCode): void
    {
        $this->marketPlaceCode = $marketplaceCode;
    }

    public function getMarketplaceCode(): ?string
    {
        return $this->marketPlaceCode;
    }

    public function setAccountId(int $accountId): void
    {
        $this->accountId = $accountId;
    }

    public function getAccountId(): ?int
    {
        return $this->accountId;
    }
}
