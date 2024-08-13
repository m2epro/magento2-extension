<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Promotion;

class Collection
{
    /** @var \Ess\M2ePro\Model\Ebay\Promotion[] */
    private array $promotions = [];

    public function add(\Ess\M2ePro\Model\Ebay\Promotion $promotion): self
    {
        $this->promotions[$promotion->getId()] = $promotion;

        return $this;
    }

    public function remove(\Ess\M2ePro\Model\Ebay\Promotion $promotion): self
    {
        unset($this->promotions[$promotion->getId()]);

        return $this;
    }

    public function has(int $id): bool
    {
        return isset($this->promotions[$id]);
    }

    public function hasByPromotionId(string $promotionId): bool
    {
        foreach ($this->promotions as $promotion) {
            if ($promotion->getPromotionId() === $promotionId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Promotion[]
     */
    public function getAll(): array
    {
        return array_values($this->promotions);
    }

    public function getByPromotionId(string $promotionId): ?\Ess\M2ePro\Model\Ebay\Promotion
    {
        foreach ($this->promotions as $promotion) {
            if ($promotion->getPromotionId() === $promotionId) {
                return $promotion;
            }
        }

        return null;
    }
}
