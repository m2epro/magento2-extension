<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;
use Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;

class MapToAsin extends Main
{
    public function execute()
    {
        $productId   = $this->getRequest()->getParam('product_id');
        $generalId   = $this->getRequest()->getParam('general_id');
        $optionsData = $this->getRequest()->getParam('options_data');
        $searchType  = $this->getRequest()->getParam('search_type');
        $searchValue = $this->getRequest()->getParam('search_value');

        if (empty($productId) || empty($generalId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        if (!$this->getHelper('Component\Amazon')->isASIN($generalId) &&
            !$this->getHelper('Data')->isISBN($generalId)
        ) {
            $this->setAjaxContent('General ID has invalid format.', false);

            return $this->getResult();
        }

        /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productId);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        $variationManager = $amazonListingProduct->getVariationManager();

        if ($variationManager->isRelationParentType() && empty($optionsData)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        if (!$listingProduct->isNotListed() || $amazonListingProduct->isGeneralIdOwner()) {
            $this->setAjaxContent('0', false);

            return $this->getResult();
        }

        $searchStatusInProgress = \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_IN_PROGRESS;

        if ($listingProduct->getData('search_settings_status') == $searchStatusInProgress) {
            $this->setAjaxContent('0', false);

            return $this->getResult();
        }

        if (!empty($searchType) && !empty($searchValue)) {
            $generalIdSearchInfo = array(
                'is_set_automatic' => false,
                'type'  => $searchType,
                'value' => $searchValue,
            );

            $amazonListingProduct->setSettings('general_id_search_info', $generalIdSearchInfo);
        }

        $amazonListingProduct->setData('general_id',$generalId);
        $amazonListingProduct->setData('search_settings_status',NULL);
        $amazonListingProduct->setData('search_settings_data',NULL);

        $amazonListingProduct->save();

        if (empty($optionsData)) {
            $this->setAjaxContent('0', false);

            return $this->getResult();
        }

        $optionsData = $this->getHelper('Data')->jsonDecode($optionsData);

        if ($variationManager->isRelationParentType()) {
            if (empty($optionsData['virtual_matched_attributes'])) {
                $matchedAttributes = $optionsData['matched_attributes'];
            } else {
                $attributesData = $optionsData['virtual_matched_attributes'];

                $matchedAttributes = array();
                $virtualMagentoAttributes = array();
                $virtualAmazonAttributes = array();

                foreach ($attributesData as $key => $value) {
                    if (strpos($key, 'virtual_magento_attributes_') !== false) {
                        $amazonAttrKey = 'virtual_magento_option_' . str_replace('virtual_magento_attributes_','',$key);
                        $virtualMagentoAttributes[$value] = $attributesData[$amazonAttrKey];

                        unset($attributesData[$key]);
                        unset($attributesData[$amazonAttrKey]);
                        continue;
                    }

                    if (strpos($key, 'virtual_amazon_attributes_') !== false) {
                        $amazonAttrKey = 'virtual_amazon_option_' . str_replace('virtual_amazon_attributes_','',$key);
                        $virtualAmazonAttributes[$value] = $attributesData[$amazonAttrKey];

                        unset($attributesData[$key]);
                        unset($attributesData[$amazonAttrKey]);
                        continue;
                    }

                    if (strpos($key, 'magento_attributes_') !== false) {
                        $amazonAttrKey = 'amazon_attributes_' . str_replace('magento_attributes_','',$key);
                        $matchedAttributes[$value] = $attributesData[$amazonAttrKey];

                        unset($attributesData[$key]);
                        unset($attributesData[$amazonAttrKey]);
                        continue;
                    }
                }
            }

            $channelVariationsSet = array();
            foreach ($optionsData['variations']['set'] as $attribute => $options) {
                $channelVariationsSet[$attribute] = array_values($options);
            }

            /** @var ParentRelation $parentTypeModel */
            $parentTypeModel = $variationManager->getTypeModel();

            if (!empty($virtualMagentoAttributes)) {
                $parentTypeModel->setVirtualProductAttributes($virtualMagentoAttributes);
            } else if (!empty($virtualAmazonAttributes)) {
                $parentTypeModel->setVirtualChannelAttributes($virtualAmazonAttributes);
            }

            $parentTypeModel->setMatchedAttributes($matchedAttributes, false);
            $parentTypeModel->setChannelAttributesSets($channelVariationsSet, false);

            $channelVariations = array();
            foreach ($optionsData['variations']['asins'] as $asin => $asinAttributes) {
                $channelVariations[$asin] = $asinAttributes['specifics'];
            }
            $parentTypeModel->setChannelVariations($channelVariations, false);

            $parentTypeModel->getProcessor()->process();

            if ($listingProduct->getMagentoProduct()->isGroupedType()) {
                $this->setAjaxContent('0', false);

                return $this->getResult();
            }

            $vocabularyHelper = $this->getHelper('Component\Amazon\Vocabulary');

            if ($vocabularyHelper->isAttributeAutoActionDisabled()) {
                $this->setAjaxContent('0', false);

                return $this->getResult();
            }

            $attributesForAddingToVocabulary = array();

            foreach ($matchedAttributes as $productAttribute => $channelAttribute) {
                if ($productAttribute == $channelAttribute) {
                    continue;
                }

                if ($vocabularyHelper->isAttributeExistsInLocalStorage($productAttribute, $channelAttribute)) {
                    continue;
                }

                if ($vocabularyHelper->isAttributeExistsInServerStorage($productAttribute, $channelAttribute)) {
                    continue;
                }

                $attributesForAddingToVocabulary[$productAttribute] = $channelAttribute;
            }

            if ($vocabularyHelper->isAttributeAutoActionNotSet()) {
                $result = array('result' => '0');

                if (!empty($attributesForAddingToVocabulary)) {
                    $result['vocabulary_attributes'] = $attributesForAddingToVocabulary;
                }

                $this->setJsonContent($result);

                return $this->getResult();
            }

            foreach ($attributesForAddingToVocabulary as $productAttribute => $channelAttribute) {
                $vocabularyHelper->addAttribute($productAttribute, $channelAttribute);
            }

            $this->setAjaxContent('0', false);

            return $this->getResult();
        }

        if (!$variationManager->isIndividualType()) {
            $this->setAjaxContent('0', false);

            return $this->getResult();
        }

        $individualTypeModel = $variationManager->getTypeModel();

        if (!$individualTypeModel->isVariationProductMatched()) {
            $this->setAjaxContent('0', false);

            return $this->getResult();
        }

        $channelVariations = array();
        foreach ($optionsData as $asin => $asinAttributes) {
            $channelVariations[$asin] = $asinAttributes['specifics'];
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Attribute $attributesMatcher */
        $attributesMatcher = $this->modelFactory->getObject('Amazon\Listing\Product\Variation\Matcher\Attribute');
        $attributesMatcher->setMagentoProduct($listingProduct->getMagentoProduct());
        $attributesMatcher->setDestinationAttributes(array_keys($channelVariations[$generalId]));

        if (!$attributesMatcher->isAmountEqual() || !$attributesMatcher->isFullyMatched()) {
            $this->setAjaxContent('0', false);

            return $this->getResult();
        }

        $matchedAttributes = $attributesMatcher->getMatchedAttributes();

        $productOptions = $variationManager->getTypeModel()->getProductOptions();
        $channelOptions = $channelVariations[$generalId];

        $vocabularyHelper = $this->getHelper('Component\Amazon\Vocabulary');

        if ($vocabularyHelper->isOptionAutoActionDisabled()) {
            $this->setAjaxContent('0', false);

            return $this->getResult();
        }

        $optionsForAddingToVocabulary = array();

        foreach ($matchedAttributes as $productAttribute => $channelAttribute) {
            $productOption = $productOptions[$productAttribute];
            $channelOption = $channelOptions[$channelAttribute];

            if ($productOption == $channelOption) {
                continue;
            }

            if ($vocabularyHelper->isOptionExistsInLocalStorage($productOption, $channelOption, $channelAttribute)) {
                continue;
            }

            if ($vocabularyHelper->isOptionExistsInServerStorage($productOption, $channelOption, $channelAttribute)) {
                continue;
            }

            $optionsForAddingToVocabulary[$channelAttribute] = array($productOption => $channelOption);
        }

        if ($vocabularyHelper->isOptionAutoActionNotSet()) {
            $result = array('result' => '0');

            if (!empty($optionsForAddingToVocabulary)) {
                $result['vocabulary_attribute_options'] = $optionsForAddingToVocabulary;
            }

            $this->setJsonContent($result);

            return $this->getResult();
        }

        foreach ($optionsForAddingToVocabulary as $channelAttribute => $options) {
            foreach ($options as $productOption => $channelOption) {
                $vocabularyHelper->addOption($productOption, $channelOption, $channelAttribute);
            }
        }

        $this->setAjaxContent('0', false);

        return $this->getResult();
    }
}