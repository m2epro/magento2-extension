<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Search;

class Custom
{
    /** @var \Ess\M2ePro\Model\Amazon\Search\Custom\Query */
    private $query;
    /** @var \Ess\M2ePro\Model\Amazon\Search\Custom\Result */
    private $result;
    /** @var \Ess\M2ePro\Model\Listing\Product */
    private $listingProduct;
    /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
    private $connectorDispatcher;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Search\Custom\Query $query,
        \Ess\M2ePro\Model\Amazon\Search\Custom\Result $result,
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $connectorDispatcher,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper
    ) {
        $this->query = $query;
        $this->result = $result;
        $this->listingProduct = $listingProduct;
        $this->connectorDispatcher = $connectorDispatcher;
        $this->exceptionHelper = $exceptionHelper;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Search\Custom\Result
     */
    public function process(): Custom\Result
    {
        if ($this->query->getIdentifierType() === null) {
            return $this->result->setStatus(Custom\Result::UNRESOLVED_IDENTIFIER_STATUS);
        }

        try {
            $responseData = $this->query->isAsin()
                ? $this->processForAsin()
                : $this->processForOtherIdentifier();
        } catch (\Exception $exception) {
            $this->exceptionHelper->process($exception);
            return $this->result->setStatus(Custom\Result::FAIL_STATUS);
        }

        if ($responseData = $this->prepareResponseData($responseData)) {
            return $this->result->setResponseData($responseData);
        }

        return $this->result->setStatus(Custom\Result::IDENTIFIER_NOT_FOUND_STATUS);
    }

    /**
     * @return array|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function processForAsin(): ?array
    {
        $requesterClassName = 'Amazon\Search\Custom\ByAsin\Requester'; // @codingStandardsIgnoreLine
        $connectorParams = $this->getConnectorParams();
        return $this->sendRequest($requesterClassName, $connectorParams);
    }

    /**
     * @return array|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function processForOtherIdentifier(): ?array
    {
        $requesterClassName = 'Amazon\Search\Custom\ByIdentifier\Requester'; // @codingStandardsIgnoreLine
        $connectorParams = $this->getConnectorParams();
        $connectorParams['query_type'] = $this->query->getOtherIdentifierType();
        return $this->sendRequest($requesterClassName, $connectorParams);
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getConnectorParams(): array
    {
        /** @var \Ess\M2ePro\Model\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->listingProduct->getChildObject();
        $isModifyChildToSimple = !$amazonListingProduct->getVariationManager()->isRelationParentType();

        return [
            'variation_bad_parent_modify_child_to_simple' => $isModifyChildToSimple,
            'query' => $this->query->getValue(),
        ];
    }

    /**
     * @param string $requesterClassName
     * @param array $connectorParams
     *
     * @return array|null
     */
    private function sendRequest(string $requesterClassName, array $connectorParams): ?array
    {
        $connector = $this->connectorDispatcher->getCustomConnector(
            $requesterClassName,
            $connectorParams,
            $this->listingProduct->getAccount()
        );

        $this->connectorDispatcher->process($connector);
        return $connector->getPreparedResponseData();
    }

    /**
     * @param mixed $responseData
     *
     * @return array|null
     */
    private function prepareResponseData($responseData): ?array
    {
        if (empty($responseData)) {
            return null;
        }

        if ($this->query->isAsin()) {
            if (is_array($responseData)) {
                return [$responseData];
            }

            return null;
        }

        return $responseData;
    }
}
