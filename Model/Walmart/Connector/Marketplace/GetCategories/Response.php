<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Connector\Marketplace\GetCategories;

class Response
{
    /** @var Response\Category[] */
    private array $categories;
    private ?int $nextPartNumber = null;
    private Response\Part $part;

    public function __construct(array $categories, Response\Part $part)
    {
        $this->categories = $categories;
        $this->part = $part;
    }

    /**
     * @return Response\Category[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getNextPartNumber(): ?int
    {
        return $this->nextPartNumber;
    }

    public function getPart(): Response\Part
    {
        return $this->part;
    }
}
