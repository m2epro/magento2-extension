<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign;

class CreateConnector extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
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

    public function getResponseData(): CreateConnectorResult
    {
        return $this->responseData;
    }

    protected function getRequestData(): array
    {
        return [
            'account' => $this->params['account'],
            'marketplace' => $this->params['marketplace'],
            'campaign' => $this->params['campaign'],
        ];
    }

    protected function getCommand(): array
    {
        return ['promotedListing', 'campaign', 'create'];
    }

    protected function validateResponse(): bool
    {
        $hasErrors = count($this->getResponse()->getMessages()->getErrorEntities()) > 0;
        $responseData = $this->getResponse()->getResponseData();

        if (!isset($responseData['campaign']) && $hasErrors) {
            return true;
        }

        return isset($responseData['campaign']);
    }

    protected function prepareResponseData(): void
    {
        $responseData = $this->getResponse()->getResponseData();

        $errorMessages = $this->getResponse()->getMessages()->getErrorEntities();
        if (count($errorMessages) > 0) {
            $this->responseData = CreateConnectorResult::createFail($errorMessages);

            return;
        }

        $createdCampaign = $responseData['campaign'];
        $channelCampaign = new \Ess\M2ePro\Model\Ebay\PromotedListing\Channel\Dto\Campaign(
            $createdCampaign['id'],
            $createdCampaign['name'],
            $createdCampaign['status'],
            $createdCampaign['type'],
            $this->createDateTime($createdCampaign['start_date']),
            $this->createDateTime($createdCampaign['end_date']),
            (int)$this->ebayMarketplaceRepository
                ->getByNativeId((int)$createdCampaign['marketplace'])
                ->getId(),
            $createdCampaign['rate']
        );

        $this->responseData = CreateConnectorResult::createSuccess($channelCampaign);
    }

    private function createDateTime(?string $value): ?\DateTime
    {
        if ($value === null) {
            return null;
        }

        return \Ess\M2ePro\Helper\Date::createDateGmt($value);
    }
}
