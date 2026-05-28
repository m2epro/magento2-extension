<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Account;

class MultiLocationInventoryMapping
{
    /** @var MultiLocationInventoryMapping\Item[]  */
    private array $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function isEmpty(): bool
    {
        return count($this->items) == 0;
    }

    /**
     * @return MultiLocationInventoryMapping\Item[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function findByMagentoSourceCode(string $magentoSourceCode): ?MultiLocationInventoryMapping\Item
    {
        foreach ($this->items as $item) {
            if ($item->magentoSourceCode == $magentoSourceCode) {
                return $item;
            }
        }

        return null;
    }
}
