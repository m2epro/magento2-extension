<?php

namespace Ess\M2ePro\Model\Dashboard\Sales;

class Cache
{
    private const CACHE_LIFE_TIME = 600; // 10 min

    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $cache;
    /** @var \Ess\M2ePro\Model\Dashboard\Sales\PointFactory */
    private $pointFactory;

    public function __construct(\Ess\M2ePro\Helper\Data\Cache\Permanent $cache, PointFactory $pointFactory)
    {
        $this->cache = $cache;
        $this->pointFactory = $pointFactory;
    }

    public function getCachedPointSet(string $key, callable $handler): PointSet
    {
        return $this->getPointSet($key) ?? $this->setPointSet($key, $handler());
    }

    private function getPointSet(string $key): ?PointSet
    {
        /** @var array<array{value:float, date:string}>|null $value */
        $value = $this->cache->getValue($key);
        if ($value === null) {
            return null;
        }

        $set = $this->pointFactory->createSet();

        foreach ($value as $item) {
            $point = $this->pointFactory->createPoint($item['value'], $item['date']);
            $set->addPoint($point);
        }

        return $set;
    }

    private function setPointSet(string $key, PointSet $pointSet): PointSet
    {
        $value = array_map(function (Point $point) {
            return [
                'value' => $point->getValue(),
                'date' => $point->getDate()->format('Y-m-d H:i:sP'),
            ];
        }, $pointSet->getPoints());

        $this->cache->setValue($key, $value, [], self::CACHE_LIFE_TIME);

        return $pointSet;
    }
}
