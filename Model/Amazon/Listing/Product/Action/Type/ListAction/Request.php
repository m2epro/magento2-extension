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

    protected function getActionData()
    {
        $data = [
            'sku'       => $this->validatorsData['sku'],
            'type_mode' => $this->validatorsData['list_type'],
        ];

        if ($this->validatorsData['list_type'] == self::LIST_TYPE_NEW &&
            $this->getVariationManager()->isRelationMode()) {
                $data = array_merge($data, $this->getRelationData());
        }

        $data = array_merge(
            $data,
            $this->getRequestDetails()->getRequestData(),
            $this->getRequestImages()->getRequestData()
        );

        if ($this->getVariationManager()->isRelationParentType()) {
            return $data;
        }

        if ($this->validatorsData['list_type'] == self::LIST_TYPE_NEW) {
            $data = array_merge($data, $this->getNewProductIdentifierData());
        } else {
            $data = array_merge($data, $this->getExistProductIdentifierData());
        }

        $data = array_merge(
            $data,
            $this->getRequestQty()->getRequestData(),
            $this->getRequestPrice()->getRequestData(),
            $this->getRequestShippingOverride()->getRequestData()
        );

        return $data;
    }

    //########################################

    private function getExistProductIdentifierData()
    {
        return [
            'product_id' => $this->validatorsData['general_id'],
            'product_id_type' => $this->getHelper('Data')->isISBN($this->validatorsData['general_id'])
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
