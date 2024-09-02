<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Tag;

class BlockingErrors
{
    public const RETRY_ACTION_SECONDS = 86400;

    public function getList(): array
    {
        return array_merge(
            $this->getEbayList()
        );
    }

    private function getEbayList(): array
    {
        return [
            '17',
            '36',
            '70',
            '231',
            '106',
            '240',
            '21916750',
            '21916799',
            '21919136',
            '21919188',
            '21919301',
            '21919303'
        ];
    }
}
