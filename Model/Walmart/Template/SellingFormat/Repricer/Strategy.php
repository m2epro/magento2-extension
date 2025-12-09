<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Template\SellingFormat\Repricer;

class Strategy
{
    public string $id;
    public string $title;

    public function __construct(string $id, string $title)
    {
        $this->id = $id;
        $this->title = $title;
    }
}
