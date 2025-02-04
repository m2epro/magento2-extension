<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Magento\Product\Variation;

class RawSuite
{
    private array $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function isGrouped(): bool
    {
        foreach ($this->options as $option) {
            if ($option instanceof \Magento\Catalog\Model\Product) {
                return true;
            }
        }

        return false;
    }

    /**
     * If grouped then options an array of products
     *
     * @return \Magento\Catalog\Model\Product[]|array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }
}
