<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator;

class ListType extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Validator
{
    private $childGeneralIdsForParent = array();

    private $cachedData = array();

    //########################################

    /**
     * @param array $generalIds
     * @return $this
     */
    public function setChildGeneralIdsForParent(array $generalIds)
    {
        $this->childGeneralIdsForParent = $generalIds;
        return $this;
    }

    //########################################

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
// M2ePro\TRANSLATIONS
// You cannot list this Product because for selling Bundle, Simple with custom Options or Downloadable With Separated Links Magento Products on Amazon the ASIN has to be found manually. Please use manual search to find the required ASIN and try again.
            $this->addMessage('You cannot list this Product because for selling Bundle, Simple
                               With Custom Options or Downloadable With Separated Links Magento Products
                               on Amazon the ASIN/ISBN has to be found manually.
                               Please use Manual Search to find the required ASIN/ISBN and try again.');
            return false;
        }

        $generalId = $this->recognizeBySearchGeneralId();
        if ($generalId === false) {
            return false;
        }

        if (!is_null($generalId)) {

            if ($this->getVariationManager()->isRelationParentType()) {
                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Linking $linkingObject */
                $linkingObject = $this->modelFactory->getObject(
                    'Amazon\Listing\Product\Action\Type\ListAction\Linking'
                );
                $linkingObject->setListingProduct($this->getListingProduct());
                $linkingObject->setGeneralId($generalId);
                $linkingObject->setSku($this->getData('sku'));
                $linkingObject->setAdditionalData(reset($this->cachedData['amazon_data'][$generalId]));

                $generalIdType = $this->getHelper('Data')->isISBN($generalId) ? 'ISBN' : 'ASIN';

                if ($linkingObject->link()) {
// M2ePro\TRANSLATIONS
// Magento Parent Product was successfully linked to Amazon Parent Product by %general_id_type% "%general_id%" via Search Settings.
                    $this->addMessage(
                        $this->getHelper('Module\Log')->encodeDescription(
                            'Magento Parent Product was successfully linked
                             to Amazon Parent Product by %general_id_type% "%general_id%" via Search Settings.',
                            array('!general_id_type' => $generalIdType, '!general_id' => $generalId)
                        ),
                        \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_SUCCESS
                    );
                } else {
// M2ePro\TRANSLATIONS
// Unexpected error has occurred while trying to link Magento Parent Product, although the %general_id_type% "%general_id%" was successfully found on Amazon.
                    $this->addMessage(
                        $this->getHelper('Module\Log')->encodeDescription(
                            'Unexpected error has occurred while trying to link Magento Parent Product,
                             although the %general_id_type% "%general_id%" was successfully found on Amazon.',
                            array('general_id' => $generalId, 'general_id_type' => $generalIdType)
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

        if (!is_null($generalId)) {
            $this->setGeneralId($generalId);
            $this->setListType(
                \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Request::LIST_TYPE_EXIST
            );

            return true;
        }

        $generalId = $this->recognizeByDescriptionTemplateWorldwideId();
        if ($generalId === false) {
            return false;
        }

        if (!is_null($generalId)) {
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

        $generalId = $this->getAmazonListingProduct()->getListingSource()->getSearchGeneralId();
        if (empty($generalId)) {
            return null;
        }
// M2ePro\TRANSLATIONS
// 'M2E Pro did not use New ASIN/ISBN Creation feature assigned because settings for ASIN/ISBN Search were specified in Listing Search Settings and a value %general_id% were set in Magento Attribute for that Product.'
        if ($this->getAmazonListingProduct()->isGeneralIdOwner()) {
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'M2E Pro did not use New ASIN/ISBN Creation feature assigned because settings
                    for ASIN/ISBN Search were specified in Listing Search Settings and a value
                    %general_id% were set in Magento Attribute for that Product.',
                    array('!general_id' => $generalId)
                ),
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
            );
        }

        if (!$this->getHelper('Component\Amazon')->isASIN($generalId) &&
            !$this->getHelper('Data')->isISBN($generalId)
        ) {
// M2ePro\TRANSLATIONS
// The value "%general_id%" provided for ASIN/ISBN in Listing Search Settings is invalid. Please set the correct value and try again.
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'The value "%general_id%" provided for ASIN/ISBN in Listing Search Settings is invalid.
                     Please set the correct value and try again.',
                    array('!general_id' => $generalId)
                )
            );

            return false;
        }

        $generalIdType = $this->getHelper('Data')->isISBN($generalId) ? 'ISBN' : 'ASIN';

        $amazonData = $this->getDataFromAmazon($generalId);
        if (empty($amazonData)) {
// M2ePro\TRANSLATIONS
// %general_id_type% %general_id% provided in Listing Search Settings is not found on Amazon. Please set the correct value and try again. Note: Due to Amazon API restrictions M2E Pro might not see all the existing Products on Amazon.
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    '%general_id_type% %general_id% provided in Listing Search Settings
                     is not found on Amazon.
                     Please set the correct value and try again.
                     Note: Due to Amazon API restrictions M2E Pro
                     might not see all the existing Products on Amazon.',
                    array('!general_id_type' => $generalIdType, '!general_id' => $generalId)
                )
            );

            return false;
        }

        if (count($amazonData) > 1) {
// M2ePro\TRANSLATIONS
// There is more than one Product found on Amazon using Search by %general_id_type% %general_id%. First, you should select certain one using manual search.
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'There is more than one Product found on Amazon using Search
                     by %general_id_type% %general_id%.
                     First, you should select certain one using manual search.',
                    array('!general_id_type' => $generalIdType, '!general_id' => $generalId)
                )
            );

            return false;
        }

        $amazonData = reset($amazonData);

        if (!empty($amazonData['parentage']) && $amazonData['parentage'] == 'parent') {
            if (!$this->getVariationManager()->isRelationParentType()) {
// M2ePro\TRANSLATIONS
// Amazon Parent Product was found using Search by %general_id_type% %general_id% while Simple or Child Product ASIN/ISBN is required.
                $this->addMessage(
                    $this->getHelper('Module\Log')->encodeDescription(
                        'Amazon Parent Product was found using Search by %general_id_type% %general_id%
                         while Simple or Child Product ASIN/ISBN is required.',
                        array('!general_id_type' => $generalIdType, '!general_id' => $generalId)
                    )
                );

                return false;
            }

            if (!empty($amazonData['bad_parent'])) {
// M2ePro\TRANSLATIONS
// Working with Amazon Parent Product found using Search by %general_id_type% %general_id% is limited due to Amazon API restrictions.
                $this->addMessage(
                    $this->getHelper('Module\Log')->encodeDescription(
                        'Working with Amazon Parent Product found using Search by %general_id_type% %general_id%
                         is limited due to Amazon API restrictions.',
                        array('!general_id_type' => $generalIdType, '!general_id' => $generalId)
                    )
                );

                return false;
            }

            $magentoAttributes = $this->getVariationManager()->getTypeModel()->getProductAttributes();
            $amazonDataAttributes = array_keys($amazonData['variations']['set']);

            if (count($magentoAttributes) != count($amazonDataAttributes)) {
// M2ePro\TRANSLATIONS
// The number of Variation Attributes of the Amazon Parent Product found using Search by %general_id_type% %general_id% does not match the number of Variation Attributes of the Magento Parent Product.
                $this->addMessage(
                    $this->getHelper('Module\Log')->encodeDescription(
                        'The number of Variation Attributes of the Amazon Parent Product found
                         using Search by %general_id_type% %general_id% does not match the number
                         of Variation Attributes of the Magento Parent Product.',
                        array('!general_id_type' => $generalIdType, '!general_id' => $generalId)
                    )
                );

                return false;
            }

            return $generalId;
        }

        if ($this->getVariationManager()->isRelationParentType()) {
// M2ePro\TRANSLATIONS
// Amazon Simple or Child Product was found using Search by %general_id_type% %general_id% while Parent Product ASIN/ISBN is required.
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'Amazon Simple or Child Product was found using Search by %general_id_type% %general_id%
                     while Parent Product ASIN/ISBN is required.',
                    array('!general_id_type' => $generalIdType, '!general_id' => $generalId)
                )
            );

            return false;
        }

        return $generalId;
    }

    private function recognizeBySearchWorldwideId()
    {
        if ($this->getVariationManager()->isRelationMode()) {
            return null;
        }

        $worldwideId = $this->getAmazonListingProduct()->getListingSource()->getSearchWorldwideId();
        if (empty($worldwideId)) {
            return null;
        }

// M2ePro\TRANSLATIONS
// 'M2E Pro did not use New ASIN/ISBN Creation feature assigned because settings for UPC/EAN Search were specified in Listing Search Settings and a value %worldwide_id% were set in Magento Attribute for that Product.'
        $changingListTypeMessage = $this->getHelper('Module\Log')->encodeDescription(
            'M2E Pro did not use New ASIN/ISBN Creation feature assigned because settings
            for UPC/EAN Search were specified in Listing Search Settings and a value
            %worldwide_id% were set in Magento Attribute for that Product.',
            array('!worldwide_id' => $worldwideId)
        );

        if (!$this->getHelper('Data')->isUPC($worldwideId) && !$this->getHelper('Data')->isEAN($worldwideId)) {
            if ($this->getAmazonListingProduct()->isGeneralIdOwner()) {
                $this->addMessage(
                    $changingListTypeMessage,\Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
                );
            }

// M2ePro\TRANSLATIONS
// The value "%worldwide_id%" provided for UPC/EAN in Listing Search Settings is invalid. Please set the correct value and try again.
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'The value "%worldwide_id%" provided for UPC/EAN in Listing Search Settings is invalid.
                     Please set the correct value and try again.',
                    array('!worldwide_id' => $worldwideId)
                )
            );

            return false;
        }

        $worldwideIdType = $this->getHelper('Data')->isUPC($worldwideId) ? 'UPC' : 'EAN';

        $amazonData = $this->getDataFromAmazon($worldwideId);
        if (empty($amazonData)) {
            if ($this->getAmazonListingProduct()->isGeneralIdOwner()) {
                return null;
            }

// M2ePro\TRANSLATIONS
// %worldwide_id_type% %worldwide_id% provided in Listing Search Settings is not found on Amazon. Please set Description Policy to create New ASIN/ISBN.
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    '%worldwide_id_type% %worldwide_id% provided in Search Settings
                     is not found on Amazon. Please set Description Policy to create New ASIN/ISBN.',
                    array('!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId)
                )
            );

            return false;
        }

        if ($this->getAmazonListingProduct()->isGeneralIdOwner()) {
            $this->addMessage(
                $changingListTypeMessage, \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
            );
        }

        if (count($amazonData) > 1) {
// M2ePro\TRANSLATIONS
// There is more than one Product found on Amazon using Search by %worldwide_id_type% %worldwide_id%. First, you should select certain one using manual search.
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'There is more than one Product found on Amazon using Search by %worldwide_id_type% %worldwide_id%.
                     First, you should select certain one using manual search.',
                    array('!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId)
                )
            );

            return false;
        }

        $amazonData = reset($amazonData);

        if (!empty($amazonData['parentage']) &&
            $amazonData['parentage'] == 'parent' &&
            empty($amazonData['requested_child_id'])
        ) {
// M2ePro\TRANSLATIONS
// Amazon Parent Product was found using Search by %worldwide_id_type% %worldwide_id% while Simple or Child Product ASIN/ISBN is required.
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'Amazon Parent Product was found using Search by %worldwide_id_type% %worldwide_id%
                     while Simple or Child Product ASIN/ISBN is required.',
                    array('!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId)
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

    private function recognizeByDescriptionTemplateWorldwideId()
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

        $worldwideId = $this->getAmazonListingProduct()->getDescriptionTemplateSource()->getWorldwideId();
        if (empty($worldwideId)) {
            return null;
        }

        if (!$this->getHelper('Data')->isUPC($worldwideId) && !$this->getHelper('Data')->isEAN($worldwideId)) {
// M2ePro\TRANSLATIONS
// The value "%worldwide_id%" provided for UPC/EAN in Description Policy is invalid. Please provide the correct value and try again.
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'The value "%worldwide_id%" provided for UPC/EAN in Description Policy is invalid.
                     Please provide the correct value and try again.',
                    array('!worldwide_id' => $worldwideId)
                )
            );

            return false;
        }

        $worldwideIdType = $this->getHelper('Data')->isUPC($worldwideId) ? 'UPC' : 'EAN';

        $amazonData = $this->getDataFromAmazon($worldwideId);
        if (empty($amazonData)) {
            return null;
        }

        if (count($amazonData) > 1) {
// M2ePro\TRANSLATIONS
// There is more than one Product found on Amazon using %worldwide_id_type% %worldwide_id% provided in Description Policy. Please provide the correct value and try again.
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'There is more than one Product found on Amazon using %worldwide_id_type% %worldwide_id%
                     provided in Description Policy. Please provide the correct value and try again.',
                    array('!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId)
                )
            );

            return false;
        }

        $amazonData = reset($amazonData);

        if (!empty($amazonData['parentage']) &&
            $amazonData['parentage'] == 'parent' &&
            empty($amazonData['requested_child_id'])
        ) {
// M2ePro\TRANSLATIONS
// Amazon Parent Product was found using %worldwide_id_type% %worldwide_id% provided in Description Policy while Simple or Child Product is required. Please provide the correct value and try again.
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'Amazon Parent Product was found using %worldwide_id_type% %worldwide_id%
                     provided in Description Policy while Simple or Child Product is required.
                     Please provide the correct value and try again.',
                    array('!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId)
                )
            );

            return false;
        }

        $generalId       = $amazonData['product_id'];
        $parentGeneralId = null;

        if (!empty($amazonData['requested_child_id'])) {
            $parentGeneralId = $generalId;
            $generalId       = $amazonData['requested_child_id'];
        }

        if (!$this->getVariationManager()->isRelationChildType()) {
            return $generalId;
        }

        if (empty($amazonData['requested_child_id']) || !empty($amazonData['bad_parent'])) {
// M2ePro\TRANSLATIONS
// The Product found on Amazon using %worldwide_id_type% %worldwide_id% provided in Description Policy is not a Child Product. Linking was failed because only Child Product is required. Please provide the correct value and try again.
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'The Product found on Amazon using %worldwide_id_type% %worldwide_id%
                     provided in Description Policy is not a Child Product.
                     Linking was failed because only Child Product is required.
                     Please provide the correct value and try again.',
                    array('!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId)
                )
            );

            return false;
        }

        if ($this->isExistInChildGeneralIdsForParent($generalId)) {
// M2ePro\TRANSLATIONS
// The Product with the same %worldwide_id_type% %worldwide_id% provided in Description Policy was found on Amazon. Linking was failed because this %worldwide_id_type% has already been assigned to another Child Product of this parent. Please provide the correct value and try again.
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'The Product with the same %worldwide_id_type% %worldwide_id% provided in Description Policy
                     was found on Amazon. Linking was failed because this %worldwide_id% has already been assigned
                     to another Child Product of this parent.
                     Please provide the correct value and try again.',
                    array('!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId)
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
// M2ePro\TRANSLATIONS
// The Product was found on Amazon using %worldwide_id_type% %worldwide_id% provided in Description Policy. Linking was failed because found Child Product is related to different Parent. Please provide the correct value and try again.
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'The Product was found on Amazon using %worldwide_id_type% %worldwide_id%
                     provided in Description Policy. Linking was failed because found Child Product is related to
                     different Parent. Please provide the correct value and try again.',
                    array('!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId)
                )
            );

            return false;
        }

        $parentChannelVariations = $parentAmazonListingProduct->getVariationManager()
            ->getTypeModel()
            ->getChannelVariations();

        if (!isset($parentChannelVariations[$generalId])) {
// M2ePro\TRANSLATIONS
// The Product was found on Amazon using %worldwide_id_type% %worldwide_id% provided in Description Policy. Linking was failed because the respective Parent has no Child Product with required combination of the Variation Attributes values. Please provide the correct value and try again.
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'The Product was found on Amazon using %worldwide_id_type% %worldwide_id%
                     provided in Description Policy. Linking was failed because the respective Parent has no
                     Child Product with required combination of the Variation Attributes values.
                     Please provide the correct value and try again.',
                    array('!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId)
                )
            );

            return false;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $childProductCollection */
        $childProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $childProductCollection->addFieldToFilter('variation_parent_id', $parentAmazonListingProduct->getId());
        $existedChildGeneralIds = $childProductCollection->getColumnValues('general_id');

        if (in_array($generalId, $existedChildGeneralIds)) {
// M2ePro\TRANSLATIONS
// The Product was found on Amazon using %worldwide_id_type% %worldwide_id% provided in Description Policy. The Child Product with required combination of the Attributes values has already been added to your Parent Product. Please provide the correct value and try again.
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'The Product was found on Amazon using %worldwide_id_type% %worldwide_id%
                     provided in Description Policy. The Child Product with required combination
                     of the Attributes values has already been added to your Parent Product.
                     Please provide the correct value and try again.',
                    array('!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId)
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
// M2ePro\TRANSLATIONS
// Product cannot be Listed because ASIN/ISBN is not assigned, UPC/EAN value is not provided and the Search Settings are invalid. Please set the required Settings and try again.
            $this->addMessage(
                'Product cannot be Listed because ASIN/ISBN is not assigned, UPC/EAN value
                 is not provided and the Search Settings are invalid. Please set the required
                 Settings and try again.'
            );

            return false;
        }

        $descriptionTemplate = $this->getAmazonListingProduct()->getAmazonDescriptionTemplate();
        if (empty($descriptionTemplate)) {
// M2ePro\TRANSLATIONS
// Product cannot be Listed because the process of new ASIN/ISBN creation has started but Description Policy is missing. Please assign the Description Policy and try again.
            $this->addMessage(
                'Product cannot be Listed because the process of new ASIN/ISBN creation has started
                 but Description Policy is missing. Please assign the Description Policy and try again.'
            );

            return false;
        }

        if (!$descriptionTemplate->isNewAsinAccepted()) {
// M2ePro\TRANSLATIONS
// Product cannot be Listed because new ASIN/ISBN creation is disabled in the Description Policy assigned to this Product. Please enable new ASIN creation and try again.
            $this->addMessage(
                'Product cannot be Listed because new ASIN/ISBN creation is disabled in the Description
                 Policy assigned to this Product. Please enable new ASIN/ISBN creation and try again.'
            );

            return false;
        }

        if ($this->getVariationManager()->isRelationMode()) {
            $channelTheme = $this->getChannelTheme();

            if (empty($channelTheme)) {
// M2ePro\TRANSLATIONS
// Product is not Listed. The process of New ASIN/ISBN creation has been started, but the Variation Theme was not set. Please, set the Variation Theme to list this Product.
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

        $descriptionTemplateSource = $this->getAmazonListingProduct()->getDescriptionTemplateSource();

        $worldwideId = $descriptionTemplateSource->getWorldwideId();
        $registeredParameter = $descriptionTemplate->getRegisteredParameter();

        if (empty($worldwideId) && empty($registeredParameter)) {
// M2ePro\TRANSLATIONS
// Product cannot be Listed because no UPC/EAN value or Register Parameter is set in the Description Policy. Please set the required Settings and try again.
            $this->addMessage(
                'Product cannot be Listed because no UPC/EAN value or Register Parameter
                 is set in the Description Policy. Please set the required Settings and try again.'
            );

            return false;
        }

        if (empty($worldwideId)) {
            return true;
        }

        if (!$this->getHelper('Data')->isUPC($worldwideId) && !$this->getHelper('Data')->isEAN($worldwideId)) {
// M2ePro\TRANSLATIONS
// Product cannot be Listed because the value provided for UPC/EAN in the Description Policy has an invalid format. Please provide the correct value and try again.
            $this->addMessage(
                'Product cannot be Listed because the value provided for UPC/EAN in the
                 Description Policy has an invalid format. Please provide the correct value and try again.'
            );

            return false;
        }

        $worldwideIdType = $this->getHelper('Data')->isUPC($worldwideId) ? 'UPC' : 'EAN';

        $amazonData = $this->getDataFromAmazon($worldwideId);
        if (!empty($amazonData)) {
// M2ePro\TRANSLATIONS
// Product cannot be Listed. New ASIN/ISBN cannot be created because %worldwide_id_type% %worldwide_id% provided in the Description Policy has been found on Amazon. Please provide the correct value and try again.
            $this->addMessage(
                $this->getHelper('Module\Log')->encodeDescription(
                    'Product cannot be Listed. New ASIN/ISBN cannot be created because %worldwide_id_type%
                     %worldwide_id% provided in the Description Policy has been found on Amazon.
                     Please provide the correct value and try again.',
                    array('!worldwide_id_type' => $worldwideIdType, '!worldwide_id' => $worldwideId)
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

        $validation = $this->getHelper('Data');

        $idType = ($this->getHelper('Component\Amazon')->isASIN($identifier) ? 'ASIN' :
                  ($validation->isISBN($identifier)                             ? 'ISBN' :
                  ($validation->isUPC($identifier)                              ? 'UPC'  :
                  ($validation->isEAN($identifier)                              ? 'EAN'  : false))));

        if (empty($idType)) {
            return array();
        }

        $params = array(
            'item'    => $identifier,
            'id_type' => $idType,
            'variation_child_modification' => 'parent',
        );

        $searchMethod = 'byIdentifier';
        if ($idType == 'ASIN') {
            $searchMethod = 'byAsin';
            unset($params['id_type']);
        }

        $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('product', 'search', $searchMethod,
                                                               $params, null,
                                                               $this->getListingProduct()->getListing()->getAccount());

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

    // ---------------------------------------

    private function isExistInChildGeneralIdsForParent($childGeneralId)
    {
        return in_array($childGeneralId, $this->childGeneralIdsForParent);
    }

    //########################################
}