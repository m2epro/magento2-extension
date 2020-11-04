<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Request
 */
class Request extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Request
{
    const LIST_TYPE_EXIST = 'exist';
    const LIST_TYPE_NEW   = 'new';

    const PARENTAGE_PARENT = 'parent';
    const PARENTAGE_CHILD  = 'child';

    //########################################

    protected function beforeBuildDataEvent()
    {
        $additionalData = $this->getListingProduct()->getAdditionalData();

        if ($this->getListingProduct()->getMagentoProduct()->isGroupedType()) {
            $additionalData['grouped_product_mode'] = $this->getHelper('Module_Configuration')
                ->getGroupedProductMode();
        }

        unset($additionalData['synch_template_list_rules_note']);

        $this->getListingProduct()->setSettings('additional_data', $additionalData);
        $this->getListingProduct()->save();

        parent::beforeBuildDataEvent();
    }

    //########################################

    protected function getActionData()
    {
        $data = [
            'sku'       => $this->cachedData['sku'],
            'type_mode' => $this->cachedData['list_type'],
        ];

        if ($this->cachedData['list_type'] == self::LIST_TYPE_NEW && $this->getVariationManager()->isRelationMode()) {
            $data = array_merge($data, $this->getRelationData());
        }

        $data = array_merge(
            $data,
            $this->getQtyData(),
            $this->getRegularPriceData(),
            $this->getBusinessPriceData(),
            $this->getDetailsData(),
            $this->getImagesData()
        );

        if ($this->getVariationManager()->isRelationParentType()) {
            return $data;
        }

        if ($this->cachedData['list_type'] == self::LIST_TYPE_NEW) {
            $data = array_merge($data, $this->getNewProductIdentifierData());
        } else {
            $data = array_merge($data, $this->getExistProductIdentifierData());
        }

        return $data;
    }

    //########################################

    private function getExistProductIdentifierData()
    {
        return [
            'product_id' => $this->cachedData['general_id'],
            'product_id_type' => $this->getHelper('Data')->isISBN($this->cachedData['general_id'])
                ? 'ISBN' : 'ASIN',
        ];
    }

    private function getNewProductIdentifierData()
    {
        $data = [];

        $worldwideId = $this->getAmazonListingProduct()->getDescriptionTemplateSource()->getWorldwideId();

        if (!empty($worldwideId)) {
            $data['product_id']      = $worldwideId;
            $data['product_id_type'] = $this->getHelper('Data')->isUPC($worldwideId) ? 'UPC' : 'EAN';
        }

        $registeredParameter = $this->getAmazonListingProduct()
            ->getAmazonDescriptionTemplate()
            ->getRegisteredParameter();

        if (!empty($registeredParameter)) {
            $data['registered_parameter'] = $registeredParameter;
        }

        return $data;
    }

    // ---------------------------------------

    private function getRelationData()
    {
        if (!$this->getVariationManager()->isRelationMode()) {
            return [];
        }

        $descriptionTemplate = $this->getAmazonListingProduct()->getAmazonDescriptionTemplate();

        $data = [
            'product_data_nick' => $descriptionTemplate->getProductDataNick(),
            'variation_data'    => [
                'theme' => $this->getChannelTheme(),
            ],
        ];

        if ($this->getVariationManager()->isRelationParentType()) {
            $data['variation_data']['parentage'] = self::PARENTAGE_PARENT;
            return $data;
        }

        $typeModel = $this->getVariationManager()->getTypeModel();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $parentAmazonListingProduct */
        $parentAmazonListingProduct = $typeModel->getParentListingProduct()->getChildObject();

        $matchedAttributes = $parentAmazonListingProduct->getVariationManager()
            ->getTypeModel()
            ->getMatchedAttributes();

        $virtualChannelAttributes = $typeModel->getParentTypeModel()->getVirtualChannelAttributes();

        $attributes = [];
        foreach ($typeModel->getProductOptions() as $attribute => $value) {
            if (isset($virtualChannelAttributes[$attribute])) {
                continue;
            }

            $attributes[$matchedAttributes[$attribute]] = $value;
        }

        $data['variation_data'] = array_merge($data['variation_data'], [
            'parentage'  => self::PARENTAGE_CHILD,
            'parent_sku' => $parentAmazonListingProduct->getSku(),
            'attributes' => $attributes,
        ]);

        return $data;
    }

    //########################################

    private function getChannelTheme()
    {
        if (!$this->getVariationManager()->isRelationMode()) {
            return null;
        }

        if ($this->getVariationManager()->isRelationParentType()) {
            return $this->getVariationManager()->getTypeModel()->getChannelTheme();
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager $parentVariationManager */
        $parentVariationManager = $this->getVariationManager()
            ->getTypeModel()
            ->getParentListingProduct()
            ->getChildObject()
            ->getVariationManager();

        return $parentVariationManager->getTypeModel()->getChannelTheme();
    }

    //########################################
}
