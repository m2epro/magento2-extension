<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator;

use Ess\M2ePro\Helper\Data\Product\Identifier;

class ListType extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Validator
{
    /** @var \Ess\M2ePro\Helper\Module\Log */
    private $log;
    /** @var array */
    private $cachedData = [];

    public function __construct(
        \Ess\M2ePro\Helper\Module\Log $log,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($activeRecordFactory, $amazonFactory, $helperFactory, $modelFactory);

        $this->log = $log;
    }

    /**
     * @return bool
     */
    public function validate()
    {
        $generalId = $this->recognizeByListingProductGeneralId();
        if (!empty($generalId)) {
            $this->setGeneralId($generalId);
            $this->setListType(
                \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Request::LIST_TYPE_EXIST
            );

            return true;
        }

        if ($this->getVariationManager()->isIndividualType() && !$this->validateComplexMagentoProductTypes()) {
            $this->addMessage(
                'You cannot list this Product because for selling Bundle, Simple
                               With Custom Options or Downloadable With Separated Links Magento Products
                               on Amazon the ASIN/ISBN has to be found manually.
                               Please use Manual Search to find the required ASIN/ISBN and try again.'
            );

            return false;
        }

        $generalId = $this->recognizeBySearchGeneralId();
        if ($generalId === false) {
            return false;
        }

        if ($generalId !== null) {
            if ($this->getVariationManager()->isRelationParentType()) {
                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Linking $linkingObject */
                $linkingObject = $this->modelFactory->getObject(
                    'Amazon_Listing_Product_Action_Type_ListAction_Linking'
                );
                $linkingObject->setListingProduct($this->getListingProduct());
                $linkingObject->setGeneralId($generalId);
                $linkingObject->setSku($this->getData('sku'));
                $linkingObject->setAdditionalData(reset($this->cachedData['amazon_data'][$generalId]));

                $generalIdType = Identifier::isISBN($generalId) ? Identifier::ISBN : Identifier::ASIN;

                if ($linkingObject->link()) {
                    $this->addMessage(
                        $this->log->encodeDescription(
                            'Magento Parent Product was linked
                             to Amazon Parent Product by %general_id_type% "%general_id%" via Search Settings.',
                            ['!general_id_type' => $generalIdType, '!general_id' => $generalId]
                        ),
                        \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_SUCCESS
                    );
                } else {
                    $this->addMessage(
                        $this->log->encodeDescription(
                            'Unexpected error has occurred while trying to link Magento Parent Product,
                             although the %general_id_type% "%general_id%" was found on Amazon.',
                            ['general_id' => $generalId, 'general_id_type' => $generalIdType]
                        )
                    );
                }

                return false;
            }

            $this->setGeneralId($generalId);
            $this->setListType(
                \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Request::LIST_TYPE_EXIST
            );

            return true;
        }

        $generalId = $this->recognizeBySearchWorldwideId();
        if ($generalId === false) {
            return false;
        }

        if ($generalId !== null) {
            $this->setGeneralId($generalId);
            $this->setListType(
                \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Request::LIST_TYPE_EXIST
            );

            return true;
        }

        $generalId = $this->recognizeWorldwideId();
        if ($generalId === false) {
            return false;
        }

        if ($generalId !== null) {
            $this->setGeneralId($generalId);
            $this->setListType(
                \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Request::LIST_TYPE_EXIST
            );

            return true;
        }

        if ($this->validateNewProduct()) {
            $this->setListType(
                \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Request::LIST_TYPE_NEW
            );

            return true;
        }

        return false;
    }

    //########################################

    private function recognizeByListingProductGeneralId()
    {
        $generalId = $this->getAmazonListingProduct()->getGeneralId();
        if (empty($generalId)) {
            return null;
        }

        return $generalId;
    }

    private function recognizeBySearchGeneralId()
    {
        if ($this->getVariationManager()->isRelationChildType()) {
            return null;
        }

        $generalId = $this->getAmazonListingProduct()->getIdentifiers()->getGeneralId();
        if (empty($generalId)) {
            return null;
        }

        if ($this->getAmazonListingProduct()->isGeneralIdOwner()) {
            $this->addMessage(
                $this->log->encodeDescription(
                    'M2E Pro did not use New ASIN/ISBN Creation feature assigned because settings
                    for ASIN/ISBN Search were specified in Listing Search Settings and a value
                    %general_id% were set in Magento Attribute for that Product.',
                    ['!general_id' => $generalId->getIdentifier()]
                ),
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
            );
        }

        if ($generalId->hasUnresolvedType()) {
            $this->addMessage(
                $this->log->encodeDescription(
                    'The value "%general_id%" provided for ASIN/ISBN in Listing Search Settings is invalid.
                     Please set the correct value and try again.',
                    ['!general_id' => $generalId->getIdentifier()]
                )
            );

            return false;
        }

        $generalIdType = $generalId->isISBN() ? Identifier::ISBN : Identifier::ASIN;
        $amazonData = $this->getDataFromAmazon($generalId->getIdentifier());

        if (empty($amazonData)) {
            $this->addMessage(
                $this->log->encodeDescription(
                    '%general_id_type% %general_id% provided in Listing Search Settings
                     is not found on Amazon.
                     Please set the correct value and try again.
                     Note: Due to Amazon API restrictions M2E Pro
                     might not see all the existing Products on Amazon.',
                    ['!general_id_type' => $generalIdType, '!general_id' => $generalId->getIdentifier()]
                )
            );

            return false;
        }

        if (count($amazonData) > 1) {
            $this->addMessage(
                $this->log->encodeDescription(
                    'There is more than one Product found on Amazon using Search
                     by %general_id_type% %general_id%.
                     First, you should select certain one using manual search.',
                    ['!general_id_type' => $generalIdType, '!general_id' => $generalId->getIdentifier()]
                )
            );

            return false;
        }

        $amazonData = reset($amazonData);

        if (!empty($amazonData['parentage']) && $amazonData['parentage'] == 'parent') {
            if (!$this->getVariationManager()->isRelationParentType()) {
                $this->addMessage(
                    $this->log->encodeDescription(
                        'Amazon Parent Product was found using Search by %general_id_type% %general_id%
                         while Simple or Child Product ASIN/ISBN is required.',
                        ['!general_id_type' => $generalIdType, '!general_id' => $generalId->getIdentifier()]
                    )
                );

                return false;
            }

            if (!empty($amazonData['bad_parent'])) {
                $this->addMessage(
                    $this->log->encodeDescription(
                        'Working with Amazon Parent Product found using Search by %general_id_type% %general_id%
                         is limited due to Amazon API restrictions.',
                        ['!general_id_type' => $generalIdType, '!general_id' => $generalId->getIdentifier()]
                    )
                );

                return false;
            }

            $magentoAttributes = $this->getVariationManager()->getTypeModel()->getProductAttributes();
            $amazonDataAttributes = array_keys($amazonData['variations']['set']);

            if (count($magentoAttributes) != count($amazonDataAttributes)) {
                $this->addMessage(
                    $this->log->encodeDescription(
                        'The number of Variation Attributes of the Amazon Parent Product found
                         using Search by %general_id_type% %general_id% does not match the number
                         of Variation Attributes of the Magento Parent Product.',
                        ['!general_id_type' => $generalIdType, '!general_id' => $generalId->getIdentifier()]
                    )
                );

                return false;
            }

            return $generalId->getIdentifier();
        }

        if ($this->getVariationManager()->isRelationParentType()) {
            $this->addMessage(
                $this->log->encodeDescription(
                    'Amazon Simple or Child Product was found using Search by %general_id_type% %general_id%
                     while Parent Product ASIN/ISBN is required.',
                    ['!general_id_type' => $generalIdType, '!general_id' => $generalId->getIdentifier()]
                )
            );

            return false;
        }

        return $generalId->getIdentifier();
    }

    private function recognizeBySearchWorldwideId()
    {
        if ($this->getVariationManager()->isRelationMode()) {
            return null;
        }

        $worldwideId = $this->getAmazonListingProduct()->getIdentifiers()->getWorldwideId();
        if (empty($worldwideId)) {
            return null;
        }

        $changingListTypeMessage = $this->log->encodeDescription(
            'M2E Pro did not use New ASIN/ISBN Creation feature assigned because settings
            for UPC/EAN Search were specified in Listing Search Settings and a value
            %worldwide_id% were set in Magento Attribute for that Product.',
            ['!worldwide_id' => $worldwideId->getIdentifier()]
        );

        if ($worldwideId->hasUnresolvedType()) {
            if ($this->getAmazonListingProduct()->isGeneralIdOwner()) {
                $this->addMessage(
                    $changingListTypeMessage,
                    \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
                );
            }

            $this->addMessage(
                $this->log->encodeDescription(
                    'The value "%worldwide_id%" provided for UPC/EAN in Listing Search Settings is invalid.
                     Please set the correct value and try again.',
                    ['!worldwide_id' => $worldwideId->getIdentifier()]
                )
            );

            return false;
        }

        $worldwideIdType = $worldwideId->isUPC() ? Identifier::UPC : Identifier::EAN;

        $amazonData = $this->getDataFromAmazon($worldwideId->getIdentifier());
        if (empty($amazonData)) {
            if ($this->getAmazonListingProduct()->isGeneralIdOwner()) {
                return null;
            }

            $this->addMessage(
                $this->log->encodeDescription(
                    '%worldwide_id_type% %worldwide_id% provided in Search Settings
                     is not found on Amazon. Please set Description Policy to create New ASIN/ISBN.',
                    ['!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId->getIdentifier()]
                )
            );

            return false;
        }

        if ($this->getAmazonListingProduct()->isGeneralIdOwner()) {
            $this->addMessage(
                $changingListTypeMessage,
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
            );
        }

        if (count($amazonData) > 1) {
            $this->addMessage(
                $this->log->encodeDescription(
                    'There is more than one Product found on Amazon using Search by %worldwide_id_type% %worldwide_id%.
                     First, you should select certain one using manual search.',
                    ['!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId->getIdentifier()]
                )
            );

            return false;
        }

        $amazonData = reset($amazonData);

        if (
            !empty($amazonData['parentage']) &&
            $amazonData['parentage'] == 'parent' &&
            empty($amazonData['requested_child_id'])
        ) {
            $this->addMessage(
                $this->log->encodeDescription(
                    'Amazon Parent Product was found using Search by %worldwide_id_type% %worldwide_id%
                     while Simple or Child Product ASIN/ISBN is required.',
                    ['!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId->getIdentifier()]
                )
            );

            return false;
        }

        if (!empty($amazonData['requested_child_id'])) {
            return $amazonData['requested_child_id'];
        } else {
            return $amazonData['product_id'];
        }
    }

    private function recognizeWorldwideId()
    {
        if (!$this->getAmazonListingProduct()->isGeneralIdOwner()) {
            return null;
        }

        if ($this->getVariationManager()->isRelationParentType()) {
            return null;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Template\Description $descriptionTemplate */
        $descriptionTemplate = $this->getAmazonListingProduct()->getAmazonDescriptionTemplate();
        if (empty($descriptionTemplate)) {
            return null;
        }

        if (!$descriptionTemplate->isNewAsinAccepted()) {
            return null;
        }

        $productIdentifiers = $this->getAmazonListingProduct()->getIdentifiers();
        $worldwideId = $productIdentifiers->getWorldwideId();
        if (empty($worldwideId)) {
            return null;
        }

        if ($worldwideId->hasUnresolvedType()) {
            $this->addMessage(
                $this->log->encodeDescription(
                    'The value "%worldwide_id%" specified for UPC/EAN is invalid. Please check the values in
                     Magento Product and the settings under Amazon > Configuration > Main and try again.',
                    ['!worldwide_id' => $worldwideId->getIdentifier()]
                )
            );

            return false;
        }

        $worldwideIdType = $worldwideId->isUPC() ? Identifier::UPC : Identifier::EAN;

        $amazonData = $this->getDataFromAmazon($worldwideId->getIdentifier());
        if (empty($amazonData)) {
            return null;
        }

        if (count($amazonData) > 1) {
            $this->addMessage(
                $this->log->encodeDescription(
                    'There is more than one Product found on Amazon using %worldwide_id_type% %worldwide_id%
                     specified under Amazon > Configuration > Main. Please provide the correct value and try again.',
                    ['!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId->getIdentifier()]
                )
            );

            return false;
        }

        $amazonData = reset($amazonData);

        if (
            !empty($amazonData['parentage'])
            && $amazonData['parentage'] == 'parent'
            && empty($amazonData['requested_child_id'])
        ) {
            $this->addMessage(
                $this->log->encodeDescription(
                    'Amazon Parent Product was found using %worldwide_id_type% %worldwide_id% specified under
                     Amazon > Configuration > Main while Simple or Child Product is required.
                     Please provide the value for the Simple or Child Product and try again.',
                    ['!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId->getIdentifier()]
                )
            );

            return false;
        }

        $generalId = $amazonData['product_id'];
        $parentGeneralId = null;

        if (!empty($amazonData['requested_child_id'])) {
            $parentGeneralId = $generalId;
            $generalId = $amazonData['requested_child_id'];
        }

        if (!$this->getVariationManager()->isRelationChildType()) {
            return $generalId;
        }

        if (empty($amazonData['requested_child_id']) || !empty($amazonData['bad_parent'])) {
            $this->addMessage(
                $this->log->encodeDescription(
                    'The Product found on Amazon using %worldwide_id_type% %worldwide_id% specified under
                     Amazon > Configuration > Main is not a Child Product.
                     Please provide the value for the Child Product and try again.',
                    ['!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId->getIdentifier()]
                )
            );

            return false;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $parentAmazonListingProduct */
        $parentAmazonListingProduct = $this->getVariationManager()
                                           ->getTypeModel()
                                           ->getParentListingProduct()
                                           ->getChildObject();

        if ($parentAmazonListingProduct->getGeneralId() != $parentGeneralId) {
            $this->addMessage(
                $this->log->encodeDescription(
                    'The Product was found on Amazon using %worldwide_id_type% %worldwide_id% specified under
                     Amazon > Configuration > Main. The found Child Product is related to another Parent.
                     Please provide the correct value and try again.',
                    ['!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId->getIdentifier()]
                )
            );

            return false;
        }

        $parentChannelVariations = $parentAmazonListingProduct->getVariationManager()
                                                              ->getTypeModel()
                                                              ->getChannelVariations();

        if (!isset($parentChannelVariations[$generalId])) {
            $this->addMessage(
                $this->log->encodeDescription(
                    'The Product was found on Amazon using %worldwide_id_type% %worldwide_id% specified under
                     Amazon > Configuration > Main. The Parent has no Child Product with the required combination of
                     the variation attributes. Please provide the correct value and try again.',
                    ['!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId->getIdentifier()]
                )
            );

            return false;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $childProductCollection */
        $childProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $childProductCollection->addFieldToFilter('variation_parent_id', $parentAmazonListingProduct->getId());
        $existedChildGeneralIds = $childProductCollection->getColumnValues('general_id');

        if (in_array($generalId, $existedChildGeneralIds)) {
            $this->addMessage(
                $this->log->encodeDescription(
                    'The Product was found on Amazon using %worldwide_id_type% %worldwide_id% specified under
                     Amazon > Configuration > Main. The corresponding Parent has no Child Product with the required
                     combination of the variation attributes. Please provide the correct value and try again.',
                    ['!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId->getIdentifier()]
                )
            );

            return false;
        }

        return $generalId;
    }

    // ---------------------------------------

    private function validateNewProduct()
    {
        if (!$this->getAmazonListingProduct()->isGeneralIdOwner()) {
            $this->addMessage(
                'Product cannot be Listed because ASIN/ISBN is not assigned, UPC/EAN value
                 is not provided and the Search Settings are invalid. Please set the required
                 Settings and try again.'
            );

            return false;
        }

        $descriptionTemplate = $this->getAmazonListingProduct()->getAmazonDescriptionTemplate();
        if (empty($descriptionTemplate)) {
            $this->addMessage(
                'Product cannot be Listed because the process of new ASIN/ISBN creation has started
                 but Description Policy is missing. Please assign the Description Policy and try again.'
            );

            return false;
        }

        if (!$descriptionTemplate->isNewAsinAccepted()) {
            $this->addMessage(
                'Product cannot be Listed because new ASIN/ISBN creation is disabled in the Description
                 Policy assigned to this Product. Please enable new ASIN/ISBN creation and try again.'
            );

            return false;
        }

        if ($this->getVariationManager()->isRelationMode()) {
            $channelTheme = $this->getChannelTheme();

            if (empty($channelTheme)) {
                $this->addMessage(
                    'Product is not Listed. The process of New ASIN/ISBN creation has been started,
                     but the Variation Theme was not set.
                     Please, set the Variation Theme to list this Product.'
                );

                return false;
            }
        }

        if ($this->getVariationManager()->isRelationParentType()) {
            return true;
        }

        $productIdentifiers = $this->getAmazonListingProduct()->getIdentifiers();
        $worldwideId = $productIdentifiers->getWorldwideId();
        $registeredParameter = $productIdentifiers->getRegisteredParameter();

        if (empty($worldwideId) && empty($registeredParameter)) {
            $this->addMessage(
                'The Product cannot be Listed because no UPC/EAN value or Product ID Override option was set under
                 Amazon > Configuration > Main. Please set the required values and try again.'
            );

            return false;
        }

        if (empty($worldwideId)) {
            return true;
        }

        if ($worldwideId->hasUnresolvedType()) {
            $this->addMessage(
                'The Product cannot be Listed because the value specified for UPC/EAN under
                 Amazon > Configuration > Main has an invalid format.
                 Please provide the correct value and try again.'
            );

            return false;
        }

        $worldwideIdType = $worldwideId->isUPC() ? Identifier::UPC : Identifier::EAN;

        $amazonData = $this->getDataFromAmazon($worldwideId->getIdentifier());
        if (!empty($amazonData)) {
            $this->addMessage(
                $this->log->encodeDescription(
                    'New ASIN/ISBN cannot be created because %worldwide_id_type% %worldwide_id% specified under
                     Amazon > Configuration > Main have been found on Amazon.
                     Please provide the correct value and try again.',
                    ['!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId->getIdentifier()]
                )
            );

            return false;
        }

        return true;
    }

    //########################################

    private function validateComplexMagentoProductTypes()
    {
        if ($this->getMagentoProduct()->isSimpleTypeWithCustomOptions()) {
            return false;
        }

        if ($this->getMagentoProduct()->isBundleType()) {
            return false;
        }

        if ($this->getMagentoProduct()->isDownloadableTypeWithSeparatedLinks()) {
            return false;
        }

        return true;
    }

    //########################################

    private function getDataFromAmazon($identifier)
    {
        if (isset($this->cachedData['amazon_data'][$identifier])) {
            return $this->cachedData['amazon_data'][$identifier];
        }

        $idType = $this->getIdentifierType($identifier);

        if ($idType === null) {
            return [];
        }

        $params = [
            'item' => $identifier,
            'id_type' => $idType,
            'variation_child_modification' => 'parent',
        ];

        $searchMethod = 'byIdentifier';
        if ($idType == 'ASIN') {
            $searchMethod = 'byAsin';
            unset($params['id_type']);
        }

        /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcherObject */
        $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector(
            'product',
            'search',
            $searchMethod,
            $params,
            null,
            $this->getListingProduct()->getListing()->getAccount()
        );

        $dispatcherObject->process($connectorObj);
        $result = $connectorObj->getResponseData();

        foreach ($connectorObj->getResponse()->getMessages()->getEntities() as $message) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */

            if ($message->isError()) {
                $this->addMessage($message->getText());
            }
        }

        if ($searchMethod == 'byAsin') {
            return $this->cachedData['amazon_data'][$identifier] = isset($result['item']) ? [$result['item']] : [];
        }

        return $this->cachedData['amazon_data'][$identifier] = isset($result['items']) ? $result['items'] : [];
    }

    /**
     * @param string $identifier
     *
     * @return string|null
     */
    private function getIdentifierType(string $identifier): ?string
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

        return null;
    }

    //########################################

    private function getChannelTheme()
    {
        $variationManager = $this->getAmazonListingProduct()->getVariationManager();
        if (!$variationManager->isRelationMode()) {
            return null;
        }

        $typeModel = $variationManager->getTypeModel();

        if ($variationManager->isRelationChildType()) {
            $typeModel = $variationManager->getTypeModel()
                                          ->getParentListingProduct()
                                          ->getChildObject()
                                          ->getVariationManager()
                                          ->getTypeModel();
        }

        return $typeModel->getChannelTheme();
    }

    //########################################

    private function setListType($listType)
    {
        $this->setData('list_type', $listType);
    }

    private function setGeneralId($generalId)
    {
        $this->setData('general_id', $generalId);
    }

    //########################################
}
