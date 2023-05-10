<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class SetMatchedAttributes extends Main
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory */
    private $listingProductCollectionFactory;
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Vocabulary */
    protected $vocabularyHelper;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Helper\Component\Amazon\Vocabulary $vocabularyHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->vocabularyHelper = $vocabularyHelper;
    }

    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');
        $variationAttributes = $this->getRequest()->getParam('variation_attributes');

        if (empty($productId) || empty($variationAttributes)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        $matchedAttributes = array_combine(
            $variationAttributes['magento_attributes'],
            $variationAttributes['amazon_attributes']
        );

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productId);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        $typeModel = $amazonListingProduct->getVariationManager()->getTypeModel();

        if (!empty($variationAttributes['virtual_magento_attributes'])) {
            $typeModel->setVirtualProductAttributes(
                array_combine(
                    $variationAttributes['virtual_magento_attributes'],
                    $variationAttributes['virtual_magento_option']
                )
            );
        } elseif (!empty($variationAttributes['virtual_amazon_attributes'])) {
            $typeModel->setVirtualChannelAttributes(
                array_combine(
                    $variationAttributes['virtual_amazon_attributes'],
                    $variationAttributes['virtual_amazon_option']
                )
            );
        }

        $typeModel->setMatchedAttributes($matchedAttributes);

        $additionalData = $listingProduct->getAdditionalData();
        if (
            empty($additionalData['migrated_to_product_types'])
            && $amazonListingProduct->isGeneralIdOwner()
        ) {
            if ($replacements = $this->getAttributeReplacements($additionalData)) {
                unset($additionalData['backup_variation_matched_attributes']);
                if (!empty($additionalData['backup_variation_channel_attributes_sets'])) {
                    $additionalData['variation_channel_attributes_sets'] =
                        $additionalData['backup_variation_channel_attributes_sets'];
                    unset($additionalData['backup_variation_channel_attributes_sets']);
                }

                $additionalData = $this->replaceChannelAttributes(
                    $listingProduct,
                    $additionalData,
                    $replacements
                );
            }

            $additionalData['migrated_to_product_types'] = true;
            unset($additionalData['running_migration_to_product_types']);

            $listingProduct->setSettings('additional_data', $additionalData);
        }

        $typeModel->getProcessor()->process();

        $result = [
            'success' => true,
        ];

        if ($listingProduct->getMagentoProduct()->isGroupedType()) {
            $this->setJsonContent($result);

            return $this->getResult();
        }

        if ($this->vocabularyHelper->isAttributeAutoActionDisabled()) {
            $this->setJsonContent($result);

            return $this->getResult();
        }

        $attributesForAddingToVocabulary = [];

        foreach ($matchedAttributes as $productAttribute => $channelAttribute) {
            if ($productAttribute == $channelAttribute) {
                continue;
            }

            if ($this->vocabularyHelper->isAttributeExistsInLocalStorage($productAttribute, $channelAttribute)) {
                continue;
            }

            if ($this->vocabularyHelper->isAttributeExistsInServerStorage($productAttribute, $channelAttribute)) {
                continue;
            }

            $attributesForAddingToVocabulary[$productAttribute] = $channelAttribute;
        }

        if ($this->vocabularyHelper->isAttributeAutoActionNotSet()) {
            if (!empty($attributesForAddingToVocabulary)) {
                $result['vocabulary_attributes'] = $attributesForAddingToVocabulary;
            }

            $this->setJsonContent($result);

            return $this->getResult();
        }

        foreach ($attributesForAddingToVocabulary as $productAttribute => $channelAttribute) {
            $this->vocabularyHelper->addAttribute($productAttribute, $channelAttribute);
        }

        $this->setJsonContent($result);

        return $this->getResult();
    }

    private function replaceChannelAttributes(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        array $additionalData,
        array $replacements
    ): array {
        if (!empty($additionalData['variation_channel_variations'])) {
            foreach ($additionalData['variation_channel_variations'] as $asin => &$variation) {
                foreach ($replacements as $from => $to) {
                    if (isset($variation[$from])) {
                        $variation[$to] = $variation[$from];
                        unset($variation[$from]);
                    }
                }
            }
        }

        if (!empty($additionalData['variation_channel_attributes_sets'])) {
            $temp = [];
            foreach ($additionalData['variation_channel_attributes_sets'] as $attribute => $value) {
                if (isset($replacements[$attribute])) {
                    $newAttributeName = $replacements[$attribute];
                    $temp[$newAttributeName] = $value;
                } else {
                    $temp[$attribute] = $value;
                }
            }

            $additionalData['variation_channel_attributes_sets'] = $temp;
        }

        if (!empty($additionalData['variation_matched_attributes'])) {
            foreach ($additionalData['variation_matched_attributes'] as $magentoAttr => &$channelAttr) {
                if (isset($replacements[$channelAttr])) {
                    $channelAttr = $replacements[$channelAttr];
                }
            }
        }

        if (!empty($additionalData['variation_virtual_product_attributes'])) {
            $temp = [];
            foreach ($additionalData['variation_virtual_product_attributes'] as $attribute => $value) {
                if (isset($replacements[$attribute])) {
                    $newAttributeName = $replacements[$attribute];
                    $temp[$newAttributeName] = $value;
                } else {
                    $temp[$attribute] = $value;
                }
            }

            $additionalData['variation_virtual_product_attributes'] = $temp;
        }

        // 'variation_virtual_channel_attributes' does not require replacement

        $collection = $this->listingProductCollectionFactory->create([
            'childMode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
        ])->addFieldToFilter(
            'variation_parent_id',
            $listingProduct->getId()
        );
        /** @var \Ess\M2ePro\Model\Listing\Product $item */
        foreach ($collection->getItems() as $item) {
            $childData = $item->getAdditionalData();
            $isWritingRequired = false;

            if (!empty($childData['variation_correct_matched_attributes'])) {
                foreach ($childData['variation_correct_matched_attributes'] as $magentoAttr => &$channelAttr) {
                    if (isset($replacements[$channelAttr])) {
                        $channelAttr = $replacements[$channelAttr];
                        $isWritingRequired = true;
                    }
                }
            }

            if (!empty($childData['variation_channel_options'])) {
                $temp = [];
                foreach ($childData['variation_channel_options'] as $key => $value) {
                    if (isset($replacements[$key])) {
                        $newAttributeName = $replacements[$key];
                        $temp[$newAttributeName] = $value;
                        $isWritingRequired = true;
                    } else {
                        $temp[$key] = $value;
                    }
                }

                $childData['variation_channel_options'] = $temp;
            }

            if ($isWritingRequired) {
                $item->setSettings('additional_data', $childData)
                     ->save();
            }
        }

        return $additionalData;
    }

    private function getAttributeReplacements(array $additionalData): array
    {
        if (empty($additionalData['variation_matched_attributes'])) {
            return [];
        }

        $replacements = [];
        $matchedAttributes = $additionalData['variation_matched_attributes'];

        if (!empty($additionalData['backup_variation_matched_attributes'])) {
            $previousInfo = $additionalData['backup_variation_matched_attributes'];
            foreach ($matchedAttributes as $magentoAttr => $channelAttr) {
                if (isset($previousInfo[$magentoAttr])) {
                    $replacements[$previousInfo[$magentoAttr]] = $channelAttr;
                }
            }

            return $replacements;
        }

        if (!empty($additionalData['variation_channel_variations'])) {
            $item = reset($additionalData['variation_channel_variations']);
            $variationAttributesFound = array_keys($item);

            if (
                count($variationAttributesFound) === 1
                && count($matchedAttributes) === 1
            ) {
                $previousName = reset($variationAttributesFound);
                $currentName = reset($matchedAttributes);

                return [$previousName => $currentName];
            }
        }

        return [];
    }
}
