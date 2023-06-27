<?php

namespace Ess\M2ePro\Model\Dashboard\ListingProductIssues;

class IssueSet
{
    /** @var Issue[]  */
    private $issues = [];

    public function addIssue(Issue $issue)
    {
        $this->issues[] = $issue;
    }

    /**
     * @return Issue[]
     */
    public function getIssues(): array
    {
        return $this->issues;
    }
}
