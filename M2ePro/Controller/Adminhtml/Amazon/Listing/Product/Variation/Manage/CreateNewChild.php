<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage\CreateNewChild
 */
class CreateNewChild extends Main
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');
        $newChildProductData = $this->getRequest()->getParam('new_child_product');
        $createNewAsin = (int)$this->getRequest()->getParam('create_new_asin', 0);

        if (empty($productId) || empty($newChildProductData)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Listing\Product $parentListingProduct */
        $parentListingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productId);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $parentAmazonListingProduct */
        $parentAmazonListingProduct = $parentListingProduct->getChildObject();

        $parentTypeModel = $parentAmazonListingProduct->getVariationManager()->getTypeModel();

        $productOptions = array_combine(
            $newChildProductData['product']['attributes'],
            $newChildProductData['product']['options']
        );

        if ($parentTypeModel->isProductsOptionsRemoved($productOptions)) {
            $parentTypeModel->restoreRemovedProductOptions($productOptions);
        }

        $channelOptions = [];
        $generalId = null;

        if (!$createNewAsin) {
            $channelOptions = array_combine(
                $newChildProductData['channel']['attributes'],
                $newChildProductData['channel']['options']
            );

            $generalId = $parentTypeModel->getChannelVariationGeneralId($channelOptions);
        }

        $parentTypeModel->createChildListingProduct(
            $productOptions,
            $channelOptions,
            $generalId
        );

        $parentTypeModel->getProcessor()->process();

        $result = [
            'type' => 'success',
            'msg'  => $this->__('New Amazon Child Product was successfully created.')
        ];

        if ($createNewAsin) {
            $this->setJsonContent($result);

            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Helper\Component\Amazon\Vocabulary $vocabularyHelper */
        $vocabularyHelper = $this->getHelper('Component_Amazon_Vocabulary');

        if ($vocabularyHelper->isOptionAutoActionDisabled()) {
            $this->setJsonContent($result);

            return $this->getResult();
        }

        $matchedAttributes = $parentTypeModel->getMatchedAttributes();

        $optionsForAddingToVocabulary = [];

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

            $optionsForAddingToVocabulary[$channelAttribute] = [$productOption => $channelOption];
        }

        if ($vocabularyHelper->isOptionAutoActionNotSet()) {
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

        $this->setJsonContent($result);

        return $this->getResult();
    }
}
