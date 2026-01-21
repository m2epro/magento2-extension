<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate;

use Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ItemInfoLoader\ChannelItemInfo;

class ItemInfoLoader
{
    private \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $connectorDispatcher;
    private \Ess\M2ePro\Helper\Component\Ebay\Configuration $ebayConfiguration;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $connectorDispatcher,
        \Ess\M2ePro\Helper\Component\Ebay\Configuration $ebayConfiguration
    ) {
        $this->connectorDispatcher = $connectorDispatcher;
        $this->ebayConfiguration = $ebayConfiguration;
    }

    private const PARSER_TYPE = 'import';

    public function loadByListingOther(\Ess\M2ePro\Model\Listing\Other $listingOther): ChannelItemInfo
    {
        if (!$this->ebayConfiguration->getImportChannelInfo()) {
            return $this->createResponse(null);
        }
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Other $ebayListingOther */
        $ebayListingOther = $listingOther->getChildObject();

        $itemId = $ebayListingOther->getItemId();
        $account = $listingOther->getAccount();
        $marketplace = $listingOther->getMarketplace();

        $requestData = [
            'item_id' => $itemId,
            'parser_type' => self::PARSER_TYPE
        ];

        $connector = $this->createConnector($account, $marketplace, $requestData);

        $connector->process();

        return $this->createResponse($connector->getResponseData());
    }

    private function createConnector(
        \Ess\M2ePro\Model\Account $account,
        \Ess\M2ePro\Model\Marketplace $marketplace,
        array $requestData
    ): \Ess\M2ePro\Model\Connector\Command\RealTime\Virtual {
        return $this->connectorDispatcher->getVirtualConnector(
            'item',
            'get',
            'info',
            $requestData,
            'result',
            $marketplace,
            $account
        );
    }

    private function createResponse(?array $rawResponse): ChannelItemInfo
    {
        if (empty($rawResponse)) {
            return new ChannelItemInfo(
                '',
                [],
                [],
                []
            );
        }

        $specifics = [];
        foreach (($rawResponse['specifics'] ?? []) as $rawSpecific) {
            $specifics[] = new \Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ItemInfoLoader\Specific(
                $rawSpecific['name'],
                $rawSpecific['source'],
                $rawSpecific['value'],
            );
        }

        $variations = [];
        foreach (($rawResponse['variations'] ?? []) as $rawVariation) {
            $specifics = [];
            foreach (($rawVariation['specifics'] ?? []) as $variationSpecific) {
                $specifics[$variationSpecific['name']] = reset($variationSpecific['value']);
            }

            $variations[] = new \Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ItemInfoLoader\Variation(
                $rawVariation['sku'] ?? '',
                $rawVariation['picture_urls'] ?? [],
                $specifics
            );
        }

        return new ChannelItemInfo(
            $rawResponse['description'] ?? '',
            $rawResponse['picture_urls'] ?? [],
            $specifics,
            $variations
        );
    }
}
