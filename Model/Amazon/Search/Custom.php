<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Search;

use Ess\M2ePro\Helper\Data\Product\Identifier;

class Custom
{
    private const SEARCH_BY_ASIN = 'byAsin';
    private const SEARCH_BY_IDENTIFIER = 'byIdentifier';

    /** @var string */
    private $query;
    /** @var \Ess\M2ePro\Model\Listing\Product */
    private $listingProduct;
    /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
    private $connectorDispatcher;

    public function __construct(
        string $query,
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $connectorDispatcher
    ) {
        $this->query = str_replace('-', '', $query);
        $this->listingProduct = $listingProduct;
        $this->connectorDispatcher = $connectorDispatcher;
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function process(): array
    {
        // @codingStandardsIgnoreStart
        $requesters = [
            self::SEARCH_BY_ASIN       => 'Amazon\Search\Custom\ByAsin\Requester',
            self::SEARCH_BY_IDENTIFIER => 'Amazon\Search\Custom\ByIdentifier\Requester',
        ];
        // @codingStandardsIgnoreEnd

        $connector = $this->connectorDispatcher->getCustomConnector(
            $requesters[$this->getSearchMethod()],
            $this->getConnectorParams(),
            $this->listingProduct->getAccount()
        );

        $this->connectorDispatcher->process($connector);
        return $this->prepareResult($connector->getPreparedResponseData());
    }

    /**
     * @return array {}
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getConnectorParams(): array
    {
        /** @var \Ess\M2ePro\Model\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->listingProduct->getChildObject();
        $isModifyChildToSimple = !$amazonListingProduct->getVariationManager()->isRelationParentType();

        $params = [
            'variation_bad_parent_modify_child_to_simple' => $isModifyChildToSimple,
            'query'                                       => $this->query,
        ];

        if ($this->getSearchMethod() == self::SEARCH_BY_IDENTIFIER) {
            $params['query_type'] = $this->getIdentifierType();
        }

        return $params;
    }

    /**
     * @param $searchData
     *
     * @return array{type:false|string, value:string, data:array|mixed}
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function prepareResult($searchData): array
    {
        $connectorParams = $this->getConnectorParams();
        $searchMethod = $this->getSearchMethod();

        if ($searchData !== false && $searchMethod == self::SEARCH_BY_ASIN) {
            if (is_array($searchData) && !empty($searchData)) {
                $searchData = [$searchData];
            } elseif ($searchData === null) {
                $searchData = [];
            }
        }

        return [
            'type'  => $this->getIdentifierType(),
            'value' => $connectorParams['query'],
            'data'  => $searchData,
        ];
    }

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getSearchMethod(): string
    {
        return Identifier::isASIN($this->query)
            ? self::SEARCH_BY_ASIN
            : self::SEARCH_BY_IDENTIFIER;
    }

    /**
     * @return string|bool
     */
    private function getIdentifierType()
    {
        if (Identifier::isASIN($this->query)) {
            return Identifier::ASIN;
        }

        if (Identifier::isISBN($this->query)) {
            return Identifier::ISBN;
        }

        if (Identifier::isUPC($this->query)) {
            return Identifier::UPC;
        }

        if (Identifier::isEAN($this->query)) {
            return Identifier::EAN;
        }

        return false;
    }
}
