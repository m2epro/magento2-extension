<?php

namespace Ess\M2ePro\Model\Amazon\Dashboard\ListingProductIssues;

class Calculator implements \Ess\M2ePro\Model\Dashboard\ListingProductIssues\CalculatorInterface
{
    private const ISSUES_LIMIT = 5;

    /** @var \Ess\M2ePro\Model\Dashboard\ListingProductIssues\Repository */
    private $repository;
    /** @var \Ess\M2ePro\Model\Dashboard\ListingProductIssues\Repository\Mapper */
    private $mapper;

    public function __construct(
        \Ess\M2ePro\Model\Dashboard\ListingProductIssues\Repository $repository,
        \Ess\M2ePro\Model\Dashboard\ListingProductIssues\Repository\Mapper $mapper
    ) {
        $this->repository = $repository;
        $this->mapper = $mapper;
    }

    public function getTopIssues(): \Ess\M2ePro\Model\Dashboard\ListingProductIssues\IssueSet
    {
        $records = $this->repository->getGroupedRecords(\Ess\M2ePro\Helper\Component\Amazon::NICK, self::ISSUES_LIMIT);

        return $this->mapper->getIssueSetMappedWithRecords($records);
    }
}
