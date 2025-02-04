<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Magento\Product\Variation;

use Ess\M2ePro\Model\Magento\Product\Variation;

class StandardSuite
{
    private const DEFAULT_VARIATIONS = [
        'set' => [],
        'variations' => [],
        'additional' => [],
    ];

    private array $variations;
    private bool $isDefaultVariations;

    public function __construct(array $variations)
    {
        $this->setVariations($variations);
    }

    public function getVariations(): array
    {
        return $this->variations;
    }

    public function setVariations(array $variations): void
    {
        $isValid = !empty($variations['set'])
            && is_array($variations['set'])
            && !empty($variations['variations'])
            && is_array($variations['variations']);

        if ($isValid) {
            $this->variations = $variations;
            $this->isDefaultVariations = false;

            return;
        }

        $this->variations = self::DEFAULT_VARIATIONS;
        $this->isDefaultVariations = true;
    }

    public function hasGroupedAttributeLabel(): bool
    {
        if ($this->isDefaultVariations) {
            return false;
        }

        return !empty($this->variations['set'][Variation::GROUPED_PRODUCT_ATTRIBUTE_LABEL]);
    }
}
