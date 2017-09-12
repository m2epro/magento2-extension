<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Search;

class Settings extends \Ess\M2ePro\Model\AbstractModel
{
    const STEP_GENERAL_ID    = 1;
    const STEP_WORLDWIDE_ID  = 2;
    const STEP_MAGENTO_TITLE = 3;

    //########################################

    private $step = null;

    private $stepData = array();

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    private $listingProduct = null;

    //########################################

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

    //########################################

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

    private function getAllowedSteps()
    {
        return array(
            self::STEP_GENERAL_ID,
            self::STEP_WORLDWIDE_ID,
            self::STEP_MAGENTO_TITLE
        );
    }

    //########################################

    public function process()
    {
        if (!$this->validate()) {
            return false;
        }

        if (!empty($this->stepData['result'])) {
            $this->processResult();
            return true;
        }

        $this->stepData = array();

        if (!$this->setNextStep()) {
            $this->setNotFoundSearchStatus();
            return true;
        }

        $query = $this->getQueryParam();

        if (empty($query)) {
            return $this->process();
        }

        $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getCustomConnector(
            'Amazon\Search\Settings\\'.ucfirst($this->getSearchMethod()).'\Requester',
            $this->getConnectorParams(),
            $this->getListingProduct()->getAccount()
        );

        $dispatcherObject->process($connectorObj);

        return $connectorObj->getPreparedResponseData();
    }

    //########################################

    private function processResult()
    {
        $result = $this->stepData['result'];
        $params = $this->stepData['params'];

        $params['search_method'] == 'byAsin' && $result = array($result);

        if ($this->step == self::STEP_MAGENTO_TITLE) {
            $tempResult = $this->filterReceivedItemsFullTitleMatch($result);
            count($tempResult) == 1 && $result = $tempResult;
        }

        $type = 'string';
        if ($this->step != self::STEP_MAGENTO_TITLE) {
            $type = $this->getIdentifierType($params['query']);
        }

        $searchSettingsData = array(
            'type'  => $type,
            'value' => $params['query'],
        );

        if ($this->canPutResultToSuggestData($result)) {
            $searchSettingsData['data'] = $result;

            $amazonListingProduct = $this->getListingProduct()->getChildObject();

            $amazonListingProduct->setData(
                'search_settings_status',
                \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_ACTION_REQUIRED
            );
            $amazonListingProduct->setSettings('search_settings_data', $searchSettingsData);

            $amazonListingProduct->save();

            return;
        }

        $result = reset($result);

        $generalId = $this->getGeneralIdFromResult($result);

        if ($this->step == self::STEP_MAGENTO_TITLE && $result['title'] !== $params['query']) {
            $this->setNotFoundSearchStatus();
            return;
        }

        if ($this->step == self::STEP_GENERAL_ID && $generalId !== $params['query'] &&
            (!$this->getHelper('Data')->isISBN($generalId) || !$this->getHelper('Data')->isISBN($params['query']))) {

            $this->setNotFoundSearchStatus();
            return;
        }

        $generalIdSearchInfo = array(
            'is_set_automatic' => true,
            'type'  => $searchSettingsData['type'],
            'value' => $searchSettingsData['value'],
        );

        $dataForUpdate = array(
            'general_id' => $generalId,
            'general_id_search_info' => $this->getHelper('Data')->jsonEncode($generalIdSearchInfo),
            'is_isbn_general_id' => $this->getHelper('Data')->isISBN($generalId),
            'search_settings_status' => null,
            'search_settings_data'   => null,
        );

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

        $channelVariations = array();
        foreach ($result['variations']['asins'] as $asin => $asinAttributes) {
            $channelVariations[$asin] = $asinAttributes['specifics'];
        }
        $typeModel->setChannelVariations($channelVariations);

        $this->getListingProduct()->save();

        $typeModel->getProcessor()->process();
    }

    //########################################

    private function validate()
    {
        if (!is_null($this->step) && !in_array($this->step, $this->getAllowedSteps())) {
            return false;
        }

        if ($this->getVariationManager()->isIndividualType()) {
            if ($this->getListingProduct()->getMagentoProduct()->isBundleType() ||
                $this->getListingProduct()->getMagentoProduct()->isSimpleTypeWithCustomOptions() ||
                $this->getListingProduct()->getMagentoProduct()->isDownloadableTypeWithSeparatedLinks()
            ) {
                return false;
            }
        }

        return true;
    }

    private function getConnectorParams()
    {
        $params = array(
            'step' => $this->step,
            'query' => $this->getQueryParam(),
            'search_method' => $this->getSearchMethod(),
            'listing_product_id' => $this->getListingProduct()->getId(),
            'variation_bad_parent_modify_child_to_simple' => true
        );

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

    private function getQueryParam()
    {
        $validationHelper = $this->getHelper('Data');
        $amazonHelper = $this->getHelper('Component\Amazon');

        switch ($this->step) {
            case self::STEP_GENERAL_ID:

                $query = $this->getAmazonListingProduct()->getGeneralId();
                empty($query) && $query = $this->getAmazonListingProduct()->getListingSource()->getSearchGeneralId();

                if (!$amazonHelper->isASIN($query) && !$validationHelper->isISBN($query)) {
                    $query = null;
                }

                break;

            case self::STEP_WORLDWIDE_ID:

                $query = $this->getAmazonListingProduct()->getListingSource()->getSearchWorldwideId();

                if (!$validationHelper->isEAN($query) && !$validationHelper->isUPC($query)) {
                    $query = null;
                }

                break;

            case self::STEP_MAGENTO_TITLE:

                $query = null;

                if ($this->getAmazonListingProduct()->getAmazonListing()->isSearchByMagentoTitleModeEnabled()) {
                    $query = $this->getAmazonListingProduct()->getActualMagentoProduct()->getName();
                }

                break;

            default:

                $query = null;
        }

        return $query;
    }

    private function getSearchMethod()
    {
        $searchMethods = array_combine(
            $this->getAllowedSteps(), array('byAsin', 'byIdentifier', 'byQuery')
        );

        $searchMethod = $searchMethods[$this->step];

        if ($searchMethod == 'byAsin' && $this->getHelper('Data')->isISBN($this->getQueryParam())) {
            $searchMethod = 'byIdentifier';
        }

        return $searchMethod;
    }

    private function getIdentifierType($identifier)
    {
        $validation = $this->getHelper('Data');

        return ($this->getHelper('Component\Amazon')->isASIN($identifier) ? 'ASIN' :
               ($validation->isISBN($identifier)                          ? 'ISBN' :
               ($validation->isUPC($identifier)                           ? 'UPC'  :
               ($validation->isEAN($identifier)                           ? 'EAN'  : false))));
    }

    private function filterReceivedItemsFullTitleMatch($results)
    {
        $return = array();

        $magentoProductTitle = $this->getAmazonListingProduct()->getActualMagentoProduct()->getName();
        $magentoProductTitle = trim(strtolower($magentoProductTitle));

        foreach ($results as $item) {
            $itemTitle = trim(strtolower($item['title']));
            if ($itemTitle == $magentoProductTitle) {
                $return[] = $item;
            }
        }

        return $return;
    }

    private function getAttributeMatcher($result)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Attribute $attributeMatcher */
        $attributeMatcher = $this->modelFactory->getObject('Amazon\Listing\Product\Variation\Matcher\Attribute');
        $attributeMatcher->setMagentoProduct($this->getListingProduct()->getMagentoProduct());
        $attributeMatcher->setDestinationAttributes(array_keys($result['variations']['set']));

        return $attributeMatcher;
    }

    //########################################

    private function setNotFoundSearchStatus()
    {
        $amazonListingProduct = $this->getListingProduct()->getChildObject();

        $amazonListingProduct->setData(
            'search_settings_status', \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_NOT_FOUND
        );
        $amazonListingProduct->setData('search_settings_data', null);

        $amazonListingProduct->save();
    }

    //########################################
}