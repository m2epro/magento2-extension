<?php

namespace Ess\M2ePro\Model\Dashboard\ListingProductIssues\Repository;

use Ess\M2ePro\Model\Dashboard\ListingProductIssues\IssueSet;

class Mapper
{
    /** @var \Ess\M2ePro\Model\Dashboard\ListingProductIssues\IssueFactory */
    private $issueFactory;

    public function __construct(
        \Ess\M2ePro\Model\Dashboard\ListingProductIssues\IssueFactory $issueFactory
    ) {
        $this->issueFactory = $issueFactory;
    }

    /**
     * @param $records Record[]
     *
     * @return \Ess\M2ePro\Model\Dashboard\ListingProductIssues\IssueSet
     */
    public function getIssueSetMappedWithRecords(array $records): IssueSet
    {
        $set = $this->issueFactory->createSet();

        foreach ($records as $record) {
            $issue = $this->issueFactory->createIssue(
                $record->getTagId(),
                $record->getText(),
                $record->getTotal(),
                $record->getImpactRate()
            );

            $set->addIssue($issue);
        }

        return $set;
    }
}
