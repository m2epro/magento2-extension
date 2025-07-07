<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Connector\Account\GetGrantAccessUrl;

class Response
{
    private string $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
