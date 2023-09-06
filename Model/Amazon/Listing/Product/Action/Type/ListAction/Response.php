<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction;

use Ess\M2ePro\Model\Amazon\Template\ChangeProcessor\ChangeProcessorAbstract as ChangeProcessor;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Response
 */
class Response extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction */
    private $instructionResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instructionResource,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct(
            $activeRecordFactory,
            $helperFactory,
            $modelFactory,
            $data
        );
        $this->instructionResource = $instructionResource;
    }
    /**
     * @ingeritdoc
     */
    public function processSuccess(array $params = []): void
    {
        $generalId = $this->getGeneralId($params);

        $data = [
            'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED,
            'list_date' => $this->getHelper('Data')->getCurrentGmtDate(),
        ];

        $data = $this->appendStatusChangerValue($data);
        $data = $this->appendIdentifiersData($data, $generalId);
        $data = $this->appendDetailsValues($data);
        $this->addReviseInstructionForListPrice();

        $variationManager = $this->getAmazonListingProduct()->getVariationManager();

        if (!$variationManager->isRelationParentType()) {
            $data['is_afn_channel'] = \Ess\M2ePro\Model\Amazon\Listing\Product::IS_AFN_CHANNEL_NO;

            $data = $this->appendQtyValues($data);
            $data = $this->appendRegularPriceValues($data);
            $data = $this->appendBusinessPriceValues($data);
            $data = $this->appendGiftSettingsStatus($data);
        }

        if (isset($data['additional_data'])) {
            $data['additional_data'] = \Ess\M2ePro\Helper\Json::encode($data['additional_data']);
        }

        $this->getListingProduct()->addData($data);

        $this->getAmazonListingProduct()->addData($data);
        $this->getAmazonListingProduct()->setIsStoppedManually(false);

        $this->setVariationData($generalId);

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
        if ($isGeneralIdOwner !== null) {
            $data['is_general_id_owner'] = $isGeneralIdOwner;
        }

        if (!empty($generalId)) {
            $data['general_id'] = $generalId;
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
            /** @var \Ess\M2ePro\Model\Amazon\Marketplace\Details $detailsModel */
            $detailsModel = $this->modelFactory->getObject('Amazon_Marketplace_Details');
            $detailsModel->setMarketplaceId($this->getMarketplace()->getId());

            $channelAttributes = $detailsModel->getVariationThemeAttributes(
                $this->getRequestData()->getProductTypeNick(),
                $typeModel->getChannelTheme()
            );

            $typeModel->setChannelAttributesSets(array_fill_keys($channelAttributes, []), false);

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
                $channelAttributesSets[$attribute] = [];
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
        $linkingObject = $this->modelFactory->getObject('Amazon_Listing_Product_Action_Type_ListAction_Linking');
        $linkingObject->setListingProduct($this->getListingProduct());

        $linkingObject->createAmazonItem();
    }

    //########################################

    private function addReviseInstructionForListPrice(): void
    {
        $requestMetadata = $this->getRequestMetaData();
        if (!isset($requestMetadata['details_data'])) {
            return;
        }

        if (!isset($requestMetadata['details_data']['list_price'])) {
            return;
        }

        $instructionData = [
            'listing_product_id' => $this->getAmazonListingProduct()->getId(),
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'type' => ChangeProcessor::INSTRUCTION_TYPE_DETAILS_DATA_CHANGED,
            'initiator' => self::INSTRUCTION_INITIATOR,
            'priority' => 100,
        ];
        $this->instructionResource->add([$instructionData]);
    }
}
