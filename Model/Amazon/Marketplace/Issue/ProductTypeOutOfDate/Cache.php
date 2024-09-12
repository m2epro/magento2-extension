<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Marketplace\Issue\ProductTypeOutOfDate;

class Cache
{
    private const CACHE_KEY = __CLASS__;

    private \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCache;

    public function __construct(\Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCache)
    {
        $this->permanentCache = $permanentCache;
    }

    public function set(bool $value): void
    {
        $this->permanentCache->setValue(
            self::CACHE_KEY,
            $value,
            ['amazon', 'marketplace'],
            60 * 60
        );
    }

    public function get(): ?bool
    {
        $value = $this->permanentCache->getValue(self::CACHE_KEY);
        if ($value === null) {
            return null;
        }

        return (bool)$value;
    }

    public function clear(): void
    {
        $this->permanentCache->removeValue(self::CACHE_KEY);
    }
}
