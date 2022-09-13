<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Search;

use Ess\M2ePro\Helper\Data\Product\Identifier;

class Settings extends \Ess\M2ePro\Model\AbstractModel
{
    private const SEARCH_BY_ASIN = 'byAsin';
    private const SEARCH_BY_IDENTIFIER = 'byIdentifier';

    private const STEP_GENERAL_ID    = 1;
    private const STEP_WORLDWIDE_ID  = 2;

    /** @var \Ess\M2ePro\Model\Amazon\Search\Settings\CounterOfFind */
    private $counterOfFind;

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    private $listingProduct = null;
    /** @var string|null */
    private $step = null;
    /** @var array */
    private $stepData = [];

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Search\Settings\CounterOfFind $counterOfFind,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->counterOfFind = $counterOfFind;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return $this
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
        return $this;
    }

    /**
     * @param $step
     * @return $this
     */
    public function setStep($step)
    {
        $this->step = $step;
        return $this;
    }

    /**
     * @return bool
     */
    public function setNextStep()
    {
        $nextStep = (int)$this->step + 1;

        if (!in_array($nextStep, $this->getAllowedSteps())) {
            return false;
        }

        $this->step = $nextStep;
        return true;
    }

    /**
     * @return $this
     */
    public function resetStep()
    {
        $this->step = null;
        return $this;
    }

    /**
     * @param array $result
     * @return $this
     */
    public function setStepData(array $result)
    {
        $this->stepData = $result;
        return $this;
    }

    private function getListingProduct()
    {
        return $this->listingProduct;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product
     */
    private function getAmazonListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }

    private function getVariationManager()
    {
        return $this->getAmazonListingProduct()->getVariationManager();
    }

    /**
     * @return int[]
     */
    private function getAllowedSteps(): array
    {
        return [
            self::STEP_GENERAL_ID,
            self::STEP_WORLDWIDE_ID
        ];
    }

    public function process()
    {
        if (!$this->validate()) {
            return false;
        }

        if (!empty($this->stepData['result'])) {
            $this->processResult();
            return true;
        }

        $this->stepData = [];

        if (!$this->setNextStep()) {
            $this->setNotFoundSearchStatus();
            return true;
        }

        $query = $this->getQueryParam();

        if (empty($query)) {
            return $this->process();
        }

        // @codingStandardsIgnoreStart
        $requesters = [
            self::SEARCH_BY_ASIN       => 'Amazon\Search\Settings\ByAsin\Requester',
            self::SEARCH_BY_IDENTIFIER => 'Amazon\Search\Settings\ByIdentifier\Requester'
        ];
        // @codingStandardsIgnoreEnd

        /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcherObject */
        $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
        $connectorObj = $dispatcherObject->getCustomConnector(
            $requesters[$this->getSearchMethod()],
            $this->getConnectorParams(),
            $this->getListingProduct()->getAccount()
        );

        $dispatcherObject->process($connectorObj);

        return $connectorObj->getPreparedResponseData();
    }

    private function processResult()
    {
        $result = $this->stepData['result'];
        $params = $this->stepData['params'];

        if ($params['search_method'] == self::SEARCH_BY_ASIN) {
            $result = [$result];
        }

        $type = $this->getIdentifierType($params['query']);

        $searchSettingsData = [
            'type'  => $type,
            'value' => $params['query'],
        ];

        if ($this->canPutResultToSuggestData($result)) {
            $searchSettingsData['data'] = $result;
            $amazonListingProduct = $this->getListingProduct()->getChildObject();
            $amazonListingProduct->setData(
                'search_settings_status',
                \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_ACTION_REQUIRED
            );
            $amazonListingProduct->setSettings('search_settings_data', $searchSettingsData);
            $amazonListingProduct->save();

            $this->counterOfFind->increment();
            return;
        }

        $result = reset($result);

        $generalId = $this->getGeneralIdFromResult($result);

        if (
            $this->step == self::STEP_GENERAL_ID
            && $generalId !== $params['query']
            && (!Identifier::isISBN($generalId) || !Identifier::isISBN($params['query']))
        ) {
            $this->setNotFoundSearchStatus();
            return;
        }

        $generalIdSearchInfo = [
            'is_set_automatic' => true,
            'type'             => $searchSettingsData['type'],
            'value'            => $searchSettingsData['value'],
        ];

        $dataForUpdate = [
            'general_id'             => $generalId,
            'general_id_search_info' => \Ess\M2ePro\Helper\Json::encode($generalIdSearchInfo),
            'is_isbn_general_id'     => Identifier::isISBN($generalId),
            'search_settings_status' => null,
            'search_settings_data'   => null,
        ];

        $this->getListingProduct()->getChildObject()->addData($dataForUpdate)->save();

        if ($this->getVariationManager()->isRelationParentType()) {
            $this->processParentResult($result);
        }
    }

    private function processParentResult(array $result)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation $typeModel */
        $typeModel = $this->getVariationManager()->getTypeModel();

        $attributeMatcher = $this->getAttributeMatcher($result);
        if ($attributeMatcher->isAmountEqual() && $attributeMatcher->isFullyMatched()) {
            $typeModel->setMatchedAttributes($this->getAttributeMatcher($result)->getMatchedAttributes(), false);
        }

        $typeModel->setChannelAttributesSets($result['variations']['set'], false);

        $channelVariations = [];
        foreach ($result['variations']['asins'] as $asin => $asinAttributes) {
            $channelVariations[$asin] = $asinAttributes['specifics'];
        }
        $typeModel->setChannelVariations($channelVariations);

        $this->getListingProduct()->save();

        try {
            $typeModel->getProcessor()->process();
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
        }
    }

    private function validate()
    {
        if ($this->step !== null && !in_array($this->step, $this->getAllowedSteps())) {
            return false;
        }

        if ($this->getVariationManager()->isIndividualType()) {
            if (
                $this->getListingProduct()->getMagentoProduct()->isBundleType()
                || $this->getListingProduct()->getMagentoProduct()->isSimpleTypeWithCustomOptions()
                || $this->getListingProduct()->getMagentoProduct()->isDownloadableTypeWithSeparatedLinks()
            ) {
                return false;
            }
        }

        return true;
    }

    private function getConnectorParams()
    {
        $params = [
            'step' => $this->step,
            'query' => $this->getQueryParam(),
            'search_method' => $this->getSearchMethod(),
            'listing_product_id' => $this->getListingProduct()->getId(),
            'variation_bad_parent_modify_child_to_simple' => true
        ];

        if ($this->getVariationManager()->isVariationParent()) {
            $params['variation_bad_parent_modify_child_to_simple'] = false;
        }

        if ($this->getSearchMethod() == 'byIdentifier') {
            $params['query_type'] = $this->getIdentifierType($this->getQueryParam());
        }

        return $params;
    }

    private function getGeneralIdFromResult($result)
    {
        if ($this->getVariationManager()->isRelationParentType() || empty($result['requested_child_id'])) {
            return $result['general_id'];
        }

        return $result['requested_child_id'];
    }

    private function canPutResultToSuggestData($result)
    {
        if (count($result) > 1) {
            return true;
        }

        $result = reset($result);

        if (!$this->getVariationManager()->isRelationParentType()) {
            // result matched if it is simple or variation with requested child
            if ($result['is_variation_product'] && empty($result['requested_child_id'])) {
                return true;
            }

            return false;
        }

        if ($result['is_variation_product'] && empty($result['bad_parent'])) {
            $attributeMatcher = $this->getAttributeMatcher($result);

            if (!$attributeMatcher->isAmountEqual() || !$attributeMatcher->isFullyMatched()) {
                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * @return string|null
     */
    private function getQueryParam(): ?string
    {
        if ($this->step == self::STEP_GENERAL_ID) {
            return $this->getGeneralId();
        }

        if ($this->step == self::STEP_WORLDWIDE_ID) {
            return $this->getWorldwideId();
        }

        return null;
    }

    /**
     * @return string|null
     */
    private function getGeneralId(): ?string
    {
        if ($productGeneralId = $this->getAmazonListingProduct()->getGeneralId()) {
            $generalIdHasResolvedType = Identifier::isASIN($productGeneralId) || Identifier::isISBN($productGeneralId);
            return $generalIdHasResolvedType ? $productGeneralId : null;
        }

        if ($searchGeneralId = $this->getAmazonListingProduct()->getIdentifiers()->getGeneralId()) {
            return $searchGeneralId->hasResolvedType() ? $searchGeneralId->getIdentifier() : null;
        }

        return null;
    }

    /**
     * @return string|null
     */
    private function getWorldwideId(): ?string
    {
        $worldwideId = $this->getAmazonListingProduct()->getIdentifiers()->getWorldwideId();
        if ($worldwideId && $worldwideId->hasResolvedType()) {
            return $worldwideId->getIdentifier();
        }

        return null;
    }

    /**
     * @return string
     */
    private function getSearchMethod(): string
    {
        $searchMethods = [
            self::STEP_GENERAL_ID   => self::SEARCH_BY_ASIN,
            self::STEP_WORLDWIDE_ID => self::SEARCH_BY_IDENTIFIER
        ];

        $searchMethod = $searchMethods[$this->step];

        if (
            $searchMethod == self::SEARCH_BY_ASIN
            && Identifier::isISBN($this->getQueryParam())
        ) {
            $searchMethod = self::SEARCH_BY_IDENTIFIER;
        }

        return $searchMethod;
    }

    /**
     * @param string|null $identifier
     *
     * @return false|string
     */
    private function getIdentifierType(?string $identifier)
    {
        if (Identifier::isASIN($identifier)) {
            return Identifier::ASIN;
        }

        if (Identifier::isISBN($identifier)) {
            return Identifier::ISBN;
        }

        if (Identifier::isUPC($identifier)) {
            return Identifier::UPC;
        }

        if (Identifier::isEAN($identifier)) {
            return Identifier::EAN;
        }

        return false;
    }

    private function getAttributeMatcher($result)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Attribute $attributeMatcher */
        $attributeMatcher = $this->modelFactory->getObject('Amazon_Listing_Product_Variation_Matcher_Attribute');
        $attributeMatcher->setMagentoProduct($this->getListingProduct()->getMagentoProduct());
        $attributeMatcher->setDestinationAttributes(array_keys($result['variations']['set']));

        return $attributeMatcher;
    }

    private function setNotFoundSearchStatus()
    {
        $amazonListingProduct = $this->getListingProduct()->getChildObject();

        $amazonListingProduct->setData(
            'search_settings_status',
            \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_NOT_FOUND
        );
        $amazonListingProduct->setData('search_settings_data', null);

        $amazonListingProduct->save();
    }
}
