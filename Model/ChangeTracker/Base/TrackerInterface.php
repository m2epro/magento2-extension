<?php

namespace Ess\M2ePro\Model\ChangeTracker\Base;

interface TrackerInterface
{
    public const CHANNEL_EBAY = "ebay";
    public const CHANNEL_AMAZON  = "amazon";
    public const CHANNEL_WALMART = "walmart";

    public const TYPE_PRICE = "price";
    public const TYPE_INVENTORY = "inventory";

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @return string
     */
    public function getChannel(): string;

    /**
     * @return \Magento\Framework\DB\Select
     */
    public function getDataQuery(): \Magento\Framework\DB\Select;
}
