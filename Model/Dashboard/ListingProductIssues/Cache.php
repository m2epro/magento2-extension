<?php

namespace Ess\M2ePro\Model\Dashboard\ListingProductIssues;

class Cache
{
    private const CACHE_LIFE_TIME = 600; // 10 min

    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $cache;
    /** @var \Ess\M2ePro\Model\Dashboard\ListingProductIssues\IssueFactory */
    private $issueFactory;

    public function __construct(\Ess\M2ePro\Helper\Data\Cache\Permanent $cache, IssueFactory $issueFactory)
    {
        $this->cache = $cache;
        $this->issueFactory = $issueFactory;
    }

    public function getCachedIssueSet(string $key, callable $handler): IssueSet
    {
        return $this->getIssueSet($key) ?? $this->setIssueSet($key, $handler());
    }

    private function getIssueSet(string $key): ?IssueSet
    {
        /** @var list<array{tag_id:int, text:string, total:int, impact_rate:int|float}> $value */
        $value = $this->cache->getValue($key);

        if ($value === null) {
            return null;
        }

        $set = $this->issueFactory->createSet();

        foreach ($value as $item) {
            $issue = $this->issueFactory->createIssue(
                $item['tag_id'],
                $item['text'],
                $item['total'],
                (float)$item['impact_rate']
            );
            $set->addIssue($issue);
        }

        return $set;
    }

    private function setIssueSet(string $key, IssueSet $issueSet): IssueSet
    {
        $value = array_map(function (Issue $issue) {
            return [
                'tag_id' => $issue->getTagId(),
                'text' => $issue->getText(),
                'total' => $issue->getTotal(),
                'impact_rate' => $issue->getImpactRate(),
            ];
        }, $issueSet->getIssues());

        $this->cache->setValue($key, $value, [], self::CACHE_LIFE_TIME);

        return $issueSet;
    }
}
