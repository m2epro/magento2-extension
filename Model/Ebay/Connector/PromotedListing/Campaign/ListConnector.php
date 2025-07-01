<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign;

class ListConnector extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    private \Ess\M2ePro\Model\Ebay\Marketplace\Repository $ebayMarketplaceRepository;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Marketplace\Repository $ebayMarketplaceRepository,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        ?\Ess\M2ePro\Model\Marketplace $marketplace = null,
        ?\Ess\M2ePro\Model\Account $account = null,
        array $params = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $marketplace, $account, $params);
        $this->ebayMarketplaceRepository = $ebayMarketplaceRepository;
    }

    public function getResponseData(): ListConnectorResult
    {
        return $this->responseData;
    }

    protected function getRequestData(): array
    {
        return [
            'account' => $this->params['account'],
            'necessary_page' => $this->params['necessary_page'],
        ];
    }

    protected function getCommand(): array
    {
        return ['promotedListing', 'campaign', 'list'];
    }

    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();

        return isset($responseData['campaigns']);
    }

    protected function prepareResponseData(): void
    {
        $this->responseData = [];

        $responseData = $this->getResponse()->getResponseData();

        $campaigns = [];
        foreach ($responseData['campaigns'] as $campaign) {
            $channelPromotion = new \Ess\M2ePro\Model\Ebay\PromotedListing\Channel\Dto\Campaign(
                $campaign['id'],
                $campaign['name'],
                $campaign['status'],
                $campaign['type'],
                $this->createDateTime($campaign['start_date']),
                $this->createDateTime($campaign['end_date']),
                (int)$this->ebayMarketplaceRepository
                    ->getByNativeId((int)$campaign['marketplace'])
                    ->getId(),
                $campaign['rate']
            );

            $campaigns[] = $channelPromotion;
        }

        $this->responseData = new ListConnectorResult(
            $campaigns,
            (int)$responseData['next_page']
        );
    }

    private function createDateTime(?string $value): ?\DateTime
    {
        if ($value === null) {
            return null;
        }

        return \Ess\M2ePro\Helper\Date::createDateGmt($value);
    }
}
