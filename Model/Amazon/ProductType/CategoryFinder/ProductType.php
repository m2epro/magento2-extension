<?php

namespace Ess\M2ePro\Model\Amazon\ProductType\CategoryFinder;

class ProductType
{
    /** @var string */
    private $title;
    /** @var string */
    private $nick;
    /** @var int|null */
    private $templateId;

    public function __construct(string $title, string $nick, ?int $templateId)
    {
        $this->title = $title;
        $this->nick = $nick;
        $this->templateId = $templateId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getNick(): string
    {
        return $this->nick;
    }

    public function getTemplateId(): ?int
    {
        return $this->templateId;
    }
}
