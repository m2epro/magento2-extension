<?php

namespace Ess\M2ePro\Model\Walmart\Template\SellingFormat\Repricer;

class AccountStrategiesLoader
{
    private const CACHE_KEY_PREFIX = 'walmart_repricer_account_strategies_';
    private const CACHE_LIFETIME_SECONDS = 5 * 60;

    private \Ess\M2ePro\Helper\Data\Cache\Permanent $cache;
    private \Ess\M2ePro\Model\Walmart\Connector\Repricer\Get\Strategies\Processor $repricerGetStrategiesProcessor;
    private \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Repricer\Serializer $serializer;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Cache\Permanent $cache,
        \Ess\M2ePro\Model\Walmart\Connector\Repricer\Get\Strategies\Processor $repricerGetStrategiesProcessor,
        Serializer $serializer
    ) {
        $this->cache = $cache;
        $this->repricerGetStrategiesProcessor = $repricerGetStrategiesProcessor;
        $this->serializer = $serializer;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Repricer\Strategy[]
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function execute(\Ess\M2ePro\Model\Account $account, bool $forceLoad): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $account->getId();

        $strategies = $this->retrieveStrategiesFromCache($cacheKey);
        if ($strategies !== null && !$forceLoad) {
            return $strategies;
        }

        $strategies = $this->retrieveStrategiesFromServer($account);
        $this->saveStrategiesToCache($cacheKey, $strategies);

        return $strategies;
    }

    private function retrieveStrategiesFromCache(string $cacheKey): ?array
    {
        $cachedValue = $this->cache->getValue($cacheKey);
        if (empty($cachedValue)) {
            return null;
        }

        return $this->serializer->unserialize($cachedValue);
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Repricer\Strategy[]
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function retrieveStrategiesFromServer(\Ess\M2ePro\Model\Account $account): array
    {
        $strategyEntities = $this->repricerGetStrategiesProcessor->process($account);

        return array_map(function (\Ess\M2ePro\Model\Walmart\Connector\Repricer\Get\Strategies\StrategyEntity $dto) {
            return new Strategy($dto->name, $dto->name);
        }, $strategyEntities);
    }

    private function saveStrategiesToCache(string $cacheKey, array $strategies): void
    {
        $this->cache->setValue(
            $cacheKey,
            $this->serializer->serialize($strategies),
            [],
            self::CACHE_LIFETIME_SECONDS
        );
    }
}
