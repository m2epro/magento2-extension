<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Promotion\Channel;

class PromotionCollection
{
    /** @var \Ess\M2ePro\Model\Ebay\Promotion\Channel\Promotion[] */
    private array $promotions = [];

    public function add(Promotion $promotion): self
    {
        $this->promotions[$promotion->getPromotionId()] = $promotion;

        return $this;
    }

    public function has(string $id): bool
    {
        return isset($this->promotions[$id]);
    }

    public function get(string $id): Promotion
    {
        return $this->promotions[$id];
    }

    public function remove(string $id): self
    {
        unset($this->promotions[$id]);

        return $this;
    }

    public function getAll(): array
    {
        return $this->promotions;
    }
}
