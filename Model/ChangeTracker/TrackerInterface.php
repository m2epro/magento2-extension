<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker;

interface TrackerInterface
{
    public const CHANNEL_EBAY = "ebay";
    public const CHANNEL_AMAZON = "amazon";
    public const CHANNEL_WALMART = "walmart";

    public const TYPE_PRICE = "price";
    public const TYPE_INVENTORY = "inventory";
    public const TYPE_BEST_OFFER = "best_offer";

    public function getType(): string;

    public function getChannel(): string;

    public function getListingProductIdFrom(): int;

    public function getListingProductIdTo(): int;

    /**
     * @return array<int>
     */
    public function getAffectedMagentoProductIds(): array;

    public function getDataQuery(): \Magento\Framework\DB\Select;

    public function processQueryRow(array $row): ?array;
}
