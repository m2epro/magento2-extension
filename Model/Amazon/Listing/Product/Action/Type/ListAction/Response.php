<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction;

class Response extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response
{
    //########################################

    /**
     * @param array $params
     */
    public function processSuccess($params = array())
    {
        $generalId = $this->getGeneralId($params);

        $data = array(
            'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED,
        );

        $data = $this->appendStatusChangerValue($data);
        $data = $this->appendIdentifiersData($data, $generalId);

        $variationManager = $this->getAmazonListingProduct()->getVariationManager();

        if (!$variationManager->isRelationParentType()) {

            $data['is_afn_channel'] = \Ess\M2ePro\Model\Amazon\Listing\Product::IS_AFN_CHANNEL_NO;

            $data = $this->appendQtyValues($data);
            $data = $this->appendRegularPriceValues($data);
            $data = $this->appendBusinessPriceValues($data);
        }

        $this->getListingProduct()->addData($data);
        $this->getListingProduct()->getChildObject()->addData($data);
        $this->setVariationData($generalId);
        $this->getListingProduct()->setSetting(
            'additional_data', 'list_date', $this->getHelper('Data')->getCurrentGmtDate()
        );

        $this->getListingProduct()->save();

        if (!$variationManager->isRelationParentType()) {
            $this->createAmazonItem();
        }
    }

    //########################################

    private function appendIdentifiersData($data, $generalId)
    {
        $data['sku'] = $this->getRequestData()->getSku();

        $isGeneralIdOwner = $this->getIsGeneralIdOwner();
        if (!is_null($isGeneralIdOwner)) {
            $data['general_id_owner'] = $isGeneralIdOwner;
        }

        if (!empty($generalId)) {
            $data['general_id']         = $generalId;
            $data['is_isbn_general_id'] = $this->getHelper('Data')->isISBN($generalId);
        }

        return $data;
    }

    //########################################

    private function setVariationData($generalId)
    {
        if (empty($generalId)) {
            return;
        }

        $variationManager = $this->getAmazonListingProduct()->getVariationManager();
        if (!$variationManager->isRelationMode()) {
            return;
        }

        $typeModel = $variationManager->getTypeModel();

        if ($variationManager->isRelationParentType()) {

            $detailsModel = $this->modelFactory->getObject('Amazon\Marketplace\Details');
            $detailsModel->setMarketplaceId($this->getMarketplace()->getId());

            $channelAttributes = $detailsModel->getVariationThemeAttributes(
                $this->getRequestData()->getProductDataNick(), $typeModel->getChannelTheme()
            );

            $typeModel->setChannelAttributesSets(array_fill_keys($channelAttributes, array()), false);

            return;
        }

        if (!$this->getRequestData()->hasVariationAttributes()) {
            return;
        }

        if ($typeModel->isVariationChannelMatched()) {
            return;
        }

        $realChannelOptions = $this->getRequestData()->getVariationAttributes();

        $parentTypeModel = $typeModel->getParentTypeModel();

        if ($parentTypeModel->getVirtualChannelAttributes()) {
            $typeModel->setChannelVariation(
                array_merge($realChannelOptions, $parentTypeModel->getVirtualChannelAttributes())
            );
        } else {
            $typeModel->setChannelVariation($realChannelOptions);
        }

        // add child variation to parent
        // ---------------------------------------
        $channelVariations = (array)$parentTypeModel->getRealChannelVariations();
        $channelVariations[$generalId] = $realChannelOptions;
        $parentTypeModel->setChannelVariations($channelVariations, false);
        // ---------------------------------------

        // update parent attributes sets
        // ---------------------------------------
        $channelAttributesSets = $parentTypeModel->getRealChannelAttributesSets();
        foreach ($realChannelOptions as $attribute => $value) {
            if (!isset($channelAttributesSets[$attribute])) {
                $channelAttributesSets[$attribute] = array();
            }

            if (in_array($value, $channelAttributesSets[$attribute])) {
                continue;
            }

            $channelAttributesSets[$attribute][] = $value;
        }
        $parentTypeModel->setChannelAttributesSets($channelAttributesSets, false);
        // ---------------------------------------

        $typeModel->getParentListingProduct()->save();
    }

    //########################################

    private function getGeneralId(array $params)
    {
        if (!empty($params['general_id'])) {
            return $params['general_id'];
        }

        if ($this->getRequestData()->isTypeModeNew()) {
            return null;
        }

        return $this->getRequestData()->getProductId();
    }

    private function getIsGeneralIdOwner()
    {
        $variationManager = $this->getAmazonListingProduct()->getVariationManager();

        if ($variationManager->isRelationChildType()) {
            return null;
        }

        if ($variationManager->isRelationParentType()) {
            return \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_YES;
        }

        if ($this->getRequestData()->isTypeModeNew()) {
            return \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_YES;
        }

        return \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_NO;
    }

    //########################################

    private function createAmazonItem()
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Linking $linkingObject */
        $linkingObject = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Type\ListAction\Linking');
        $linkingObject->setListingProduct($this->getListingProduct());

        $linkingObject->createAmazonItem();
    }

    //########################################
}