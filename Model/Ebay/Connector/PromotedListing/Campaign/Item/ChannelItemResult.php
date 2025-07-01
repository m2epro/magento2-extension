<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign\Item;

class ChannelItemResult
{
    private string $id;
    private bool $isSuccess;
    /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message[] */
    private array $messages;

    /**
     * @param \Ess\M2ePro\Model\Connector\Connection\Response\Message[] $messages
     */
    public function __construct(
        string $id,
        bool $isSuccess,
        array $messages
    ) {
        $this->id = $id;
        $this->isSuccess = $isSuccess;
        $this->messages = $messages;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    /**
     * @return \Ess\M2ePro\Model\Connector\Connection\Response\Message[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
