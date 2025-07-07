<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard;

class TemplateCategoryLinkProcessorResult
{
    private ?int $categoryId;
    private ?int $secondaryCategoryId;
    private ?int $storeCategoryId;
    private ?int $secondaryStoreCategoryId;

    public function __construct(
        ?int $categoryId,
        ?int $secondaryCategoryId,
        ?int $storeCategoryId,
        ?int $secondaryStoreCategoryId
    ) {
        $this->categoryId = $categoryId;
        $this->secondaryCategoryId = $secondaryCategoryId;
        $this->storeCategoryId = $storeCategoryId;
        $this->secondaryStoreCategoryId = $secondaryStoreCategoryId;
    }
    
    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getSecondaryCategoryId(): ?int
    {
        return $this->secondaryCategoryId;
    }

    public function getStoreCategoryId(): ?int
    {
        return $this->storeCategoryId;
    }

    public function getSecondaryStoreCategoryId(): ?int
    {
        return $this->secondaryStoreCategoryId;
    }
}
