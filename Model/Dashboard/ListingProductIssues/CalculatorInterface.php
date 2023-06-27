<?php

namespace Ess\M2ePro\Model\Dashboard\ListingProductIssues;

interface CalculatorInterface
{
    public function getTopIssues(): IssueSet;
}
