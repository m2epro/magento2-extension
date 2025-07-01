<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign;

use Ess\M2ePro\Model\Ebay\PromotedListing\Channel\Dto\Campaign;

class CreateConnectorResult
{
    private const STATUS_SUCCESS = 'success';
    private const STATUS_FAIL = 'fail';

    private string $status;
    private ?Campaign $channelCampaign;
    /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message[] */
    private array $failMessages;

    private function __construct(
        string $status,
        ?Campaign $channelCampaign = null,
        array $failMessages = []
    ) {
        $this->status = $status;
        $this->failMessages = $failMessages;
        $this->channelCampaign = $channelCampaign;
    }

    public static function createSuccess(Campaign $channelCampaign): self
    {
        return new self(self::STATUS_SUCCESS, $channelCampaign, []);
    }

    public static function createFail(array $failMessages): self
    {
        return new self(self::STATUS_FAIL, null, $failMessages);
    }

    public function isFail(): bool
    {
        return $this->status === self::STATUS_FAIL;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\PromotedListing\Channel\Dto\Campaign|null
     */
    public function getChannelCampaign(): ?Campaign
    {
        return $this->channelCampaign;
    }

    /**
     * @return \Ess\M2ePro\Model\Connector\Connection\Response\Message[]
     */
    public function getFailMessages(): array
    {
        return $this->failMessages;
    }

    public function getFailMessagesAsString(): string
    {
        $messages = [];
        foreach ($this->getFailMessages() as $message) {
            $messages[] = $message->getText();
        }

        return implode("\n", $messages);
    }
}
