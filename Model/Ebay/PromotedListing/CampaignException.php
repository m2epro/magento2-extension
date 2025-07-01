<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\PromotedListing;

class CampaignException extends \Ess\M2ePro\Model\Exception\Logic
{
    private const MESSAGES_KEY = 'campaign_fail_messages';

    public function __construct(array $messages = [])
    {
        parent::__construct('', [self::MESSAGES_KEY => $messages]);
    }

    /**
     * @return \Ess\M2ePro\Model\Connector\Connection\Response\Message[]
     */
    public function getCampaignFailMessages(): array
    {
        $additionalData = $this->getAdditionalData();

        if (!isset($additionalData[self::MESSAGES_KEY])) {
            return [];
        }

        return $additionalData[self::MESSAGES_KEY];
    }
}
