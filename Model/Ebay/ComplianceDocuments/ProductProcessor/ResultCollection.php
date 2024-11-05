<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductProcessor;

class ResultCollection
{
    /** @var Result[] */
    private array $results;

    public function __construct(array $results)
    {
        $this->results = $results;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductDocumentUrlFinder\Result[]
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductDocumentUrlFinder\Result[]
     */
    public function getFailResults(): array
    {
        $failResults = [];
        foreach ($this->results as $result) {
            if ($result->isFail()) {
                $failResults[] = $result;
            }
        }

        return $failResults;
    }
}
