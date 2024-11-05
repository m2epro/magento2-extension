<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductDocumentUrlFinder;

class ResultCollection
{
    /** @var Result[] */
    private array $results;

    public function __construct(array $results)
    {
        $this->results = $results;
    }

    public function isEmpty(): bool
    {
        return count($this->results) === 0;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductDocumentUrlFinder\Result[]
     */
    public function getResults(): array
    {
        return $this->results;
    }

    public function getSuccessResults(): array
    {
        return array_filter($this->results, function (Result $result) {
            return $result->isSuccess();
        });
    }
}
