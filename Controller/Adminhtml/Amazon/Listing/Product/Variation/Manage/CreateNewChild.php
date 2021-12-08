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

        /** @var \Ess\M2ePro\Model\Listing\Product $childListingProduct */
        $childListingProduct = $parentTypeModel->createChildListingProduct(
            $productOptions,
            $channelOptions,
            $generalId
        );

        $addedProductOptions = $childListingProduct->getChildObject()->getVariationManager()
            ->getTypeModel()->getProductOptions();

        // Don't use $childListingProduct anymore, because it might be removed after calling the following method
        $parentTypeModel->getProcessor()->process();

        $isProductOptionWasAdded = false;
        foreach ($addedProductOptions as $addedProductOption) {
            if ($productOptions == $addedProductOption) {
                $isProductOptionWasAdded = true;
            }
        }

        if (!$isProductOptionWasAdded) {
            $parentListingProduct->logProductMessage(
                'New Child Product cannot be created. There is no correspondence between the Magento Attribute
                 value of a new Child Product and available Magento Attribute values of the Parent Product.',
                \Ess\M2ePro\Helper\Data::INITIATOR_USER,
                \Ess\M2ePro\Model\Listing\Log::ACTION_ADD_NEW_CHILD_LISTING_PRODUCT,
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
            );

            $message = $this->__(
                'New Child Product was not created.
 Please view <a target="_blank" href="%url%">Listing Logs</a> for details.',
                $this->getUrl(
                    '*/amazon_log_listing_product/index',
                    ['listing_product_id' => $parentListingProduct->getId()]
                )
            );

            $result = [
                'type' => 'error',
                'msg'  => $message
            ];
        } else {
            $result = [
                'type' => 'success',
                'msg'  => $this->__('New Amazon Child Product was created.')
            ];
        }

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
