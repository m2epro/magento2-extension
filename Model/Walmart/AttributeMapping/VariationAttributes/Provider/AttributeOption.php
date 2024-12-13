<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\Provider;

class AttributeOption
{
    private string $name;
    private string $title;
    private ?int $selectedMagentoOptionId;

    public function __construct(string $name, string $title, ?int $selectedMagentoOptionId)
    {
        $this->name = $name;
        $this->title = $title;
        $this->selectedMagentoOptionId = $selectedMagentoOptionId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSelectedMagentoOptionId(): ?int
    {
        return $this->selectedMagentoOptionId;
    }
}
