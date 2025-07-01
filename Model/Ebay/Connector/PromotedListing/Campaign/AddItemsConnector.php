<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign;

use Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign\Item\ChannelItemResult;

class AddItemsConnector extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    private \Ess\M2ePro\Model\Connector\Connection\Response\MessageFactory $messageFactory;

    public function __construct(
        \Ess\M2ePro\Model\Connector\Connection\Response\MessageFactory $messageFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        ?\Ess\M2ePro\Model\Marketplace $marketplace = null,
        ?\Ess\M2ePro\Model\Account $account = null,
        array $params = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $marketplace, $account, $params);
        $this->messageFactory = $messageFactory;
    }

    public function getResponseData(): AddItemsConnectorResult
    {
        return $this->responseData;
    }

    protected function getRequestData(): array
    {
        return [
            'account' => $this->params['account'],
            'campaign_id' => $this->params['campaign_id'],
            'items' => $this->params['items'],
        ];
    }

    protected function getCommand(): array
    {
        return ['promotedListing', 'campaign', 'addItems'];
    }

    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();

        return isset($responseData['items']);
    }

    protected function prepareResponseData(): void
    {
        $responseData = $this->getResponse()->getResponseData();

        $responseItems = [];
        foreach ($responseData['items'] as $item) {
            $responseItems[$item['id']] = new ChannelItemResult(
                $item['id'],
                (bool)$item['is_success'],
                $this->prepareMessages($item['messages'])
            );
        }

        $this->responseData = new AddItemsConnectorResult($responseItems);
    }

    /**
     * @return \Ess\M2ePro\Model\Connector\Connection\Response\Message[]
     */
    private function prepareMessages(array $rawMessages): array
    {
        $messages = [];
        foreach ($rawMessages as $rawMessage) {
            $messages[] = $this->messageFactory->createByResponseData($rawMessage);
        }

        return $messages;
    }
}
