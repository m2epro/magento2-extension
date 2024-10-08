<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Connector\Marketplace\GetCategories\Response\Category;

class ProductType
{
    private string $title;
    private string $nick;

    public function __construct(string $title, string $nick)
    {
        $this->title = $title;
        $this->nick = $nick;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getNick(): string
    {
        return $this->nick;
    }
}
