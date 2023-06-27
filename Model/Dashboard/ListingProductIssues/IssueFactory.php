<?php

namespace Ess\M2ePro\Model\Dashboard\ListingProductIssues;

class IssueFactory
{
    public function createIssue(
        int $tagId,
        string $text,
        int $total,
        float $impactRate
    ): Issue {
        return new Issue($tagId, $text, $total, $impactRate);
    }

    public function createSet(): IssueSet
    {
        return new IssueSet();
    }
}
