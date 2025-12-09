<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Template\SellingFormat\Repricer;

class Serializer
{
    private const KEY_ID = 'id';
    private const KEY_TITLE = 'title';

    /**
     * @param \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Repricer\Strategy[] $strategies
     */
    public function serialize(array $strategies): string
    {
        return json_encode(array_map(function (Strategy $item) {
            return [
                self::KEY_ID => $item->id,
                self::KEY_TITLE => $item->title,
            ];
        }, $strategies));
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Repricer\Strategy[]
     */
    public function unserialize(string $cachedValue): array
    {
        return array_map(function (array $item) {
            return new Strategy(
                $item[self::KEY_ID],
                $item[self::KEY_TITLE]
            );
        }, json_decode($cachedValue, true));
    }
}
