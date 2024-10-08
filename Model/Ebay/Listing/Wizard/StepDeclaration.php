<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard;

class StepDeclaration
{
    private string $nick;
    private string $route;
    private ?string $backHandlerClass;

    public function __construct(string $nick, string $route, ?string $backHandlerClass)
    {
        $this->nick = $nick;
        $this->route = $route;
        $this->backHandlerClass = $backHandlerClass;
    }

    public function getNick(): string
    {
        return $this->nick;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function hasBackHandler(): bool
    {
        return $this->backHandlerClass !== null;
    }

    public function getBackHandlerClass(): string
    {
        return (string)$this->backHandlerClass;
    }
}
