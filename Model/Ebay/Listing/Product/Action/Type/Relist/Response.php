<?php

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist;

class Response extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Response
{
    public const INSTRUCTION_TYPE_CHECK_QTY = 'success_relist_check_qty';
    public const INSTRUCTION_TYPE_CHECK_PRICE = 'success_relist_check_price';
    public const INSTRUCTION_TYPE_CHECK_TITLE = 'success_relist_check_title';
    public const INSTRUCTION_TYPE_CHECK_SUBTITLE = 'success_relist_check_subtitle';
    public const INSTRUCTION_TYPE_CHECK_DESCRIPTION = 'success_relist_check_description';
    public const INSTRUCTION_TYPE_CHECK_IMAGES = 'success_relist_check_images';
    public const INSTRUCTION_TYPE_CHECK_CATEGORIES = 'success_relist_check_categories';
    public const INSTRUCTION_TYPE_CHECK_PARTS = 'success_relist_check_parts';
    public const INSTRUCTION_TYPE_CHECK_SHIPPING = 'success_relist_check_shipping';
    public const INSTRUCTION_TYPE_CHECK_RETURN = 'success_relist_check_return';
    public const INSTRUCTION_TYPE_CHECK_OTHER = 'success_relist_check_other';

    private \Ess\M2ePro\Model\Ebay\Video\ProductProcessor $videoProductProcessor;
    private \Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductProcessor $documentsProductProcessor;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DescriptionHasher $descriptionHasher,
        \Ess\M2ePro\Model\Ebay\Video\ProductProcessor $videoProductProcessor,
        \Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductProcessor $documentsProductProcessor,
        \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataHasher $dataHasher,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $componentEbayCategoryEbay,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct(
            $descriptionHasher,
            $dataHasher,
            $componentEbayCategoryEbay,
            $activeRecordFactory,
            $helperFactory,
            $modelFactory
        );

        $this->videoProductProcessor = $videoProductProcessor;
        $this->documentsProductProcessor = $documentsProductProcessor;
    }

    public function processSuccess(array $response, array $responseParams = []): void
    {
        $this->prepareMetadata();

        $data = [
            'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED,
            'ebay_item_id' => $this->createEbayItem($response['ebay_item_id'])->getId(),
        ];

        $data = $this->appendStatusHiddenValue($data);
        $data = $this->appendStatusChangerValue($data, $responseParams);

        $data = $this->appendOnlineBidsValue($data);
        $data = $this->appendOnlineQtyValues($data);
        $data = $this->appendOnlinePriceValues($data);
        $data = $this->appendOnlineInfoDataValues($data);

        $data = $this->appendDescriptionValues($data);

        $data = $this->appendItemFeesValues($data, $response);
        $data = $this->appendStartDateEndDateValues($data, $response);
        $data = $this->appendGalleryImagesValues($data, $response);

        $data = $this->removeConditionNecessary($data);

        $data = $this->appendIsVariationMpnFilledValue($data);
        $data = $this->appendVariationsThatCanNotBeDeleted($data, $response);

        $data = $this->appendIsVariationValue($data);
        $data = $this->appendIsAuctionType($data);

        $data = $this->processRecheckInstructions($data);

        if (isset($data['additional_data'])) {
            $data['additional_data'] = \Ess\M2ePro\Helper\Json::encode($data['additional_data']);
        }

        $this->getListingProduct()->addData($data);
        $this->getListingProduct()->getChildObject()->addData($data);
        $this->getListingProduct()->removeBlockingByError();
        $this->getListingProduct()->save();

        $this->updateVariationsValues(false);

        $this->videoProductProcessor->process($this->getListingProduct());
        $this->documentsProductProcessor->process($this->getListingProduct(), true);
    }

    public function processAlreadyActive(array $response, array $responseParams = [])
    {
        $responseParams['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT;
        $this->processSuccess($response, $responseParams);
    }

    protected function processRecheckInstructions(array $data)
    {
        if (!isset($data['additional_data'])) {
            $data['additional_data'] = $this->getListingProduct()->getAdditionalData();
        }

        if (empty($data['additional_data']['recheck_properties'])) {
            return $data;
        }

        $instructionsData = [];

        foreach ($data['additional_data']['recheck_properties'] as $property) {
            $instructionType = null;
            $instructionPriority = 0;

            switch ($property) {
                case 'qty':
                    $instructionType = self::INSTRUCTION_TYPE_CHECK_QTY;
                    $instructionPriority = 80;
                    break;

                case 'price_regular':
                    $instructionType = self::INSTRUCTION_TYPE_CHECK_PRICE;
                    $instructionPriority = 60;
                    break;

                case 'title':
                    $instructionType = self::INSTRUCTION_TYPE_CHECK_TITLE;
                    $instructionPriority = 30;
                    break;

                case 'subtitle':
                    $instructionType = self::INSTRUCTION_TYPE_CHECK_SUBTITLE;
                    $instructionPriority = 30;
                    break;

                case 'description':
                    $instructionType = self::INSTRUCTION_TYPE_CHECK_DESCRIPTION;
                    $instructionPriority = 30;
                    break;

                case 'images':
                    $instructionType = self::INSTRUCTION_TYPE_CHECK_IMAGES;
                    $instructionPriority = 30;
                    break;

                case 'shipping':
                    $instructionType = self::INSTRUCTION_TYPE_CHECK_SHIPPING;
                    $instructionPriority = 30;
                    break;

                case 'return':
                    $instructionType = self::INSTRUCTION_TYPE_CHECK_RETURN;
                    $instructionPriority = 30;
                    break;

                case 'other':
                    $instructionType = self::INSTRUCTION_TYPE_CHECK_OTHER;
                    $instructionPriority = 30;
                    break;
            }

            if ($instructionType === null) {
                continue;
            }

            $instructionsData[] = [
                'listing_product_id' => $this->getListingProduct()->getId(),
                'type' => $instructionType,
                'initiator' => self::INSTRUCTION_INITIATOR,
                'priority' => $instructionPriority,
            ];
        }

        $this->activeRecordFactory->getObject('Listing_Product_Instruction')->getResource()->add($instructionsData);

        unset($data['additional_data']['recheck_properties']);

        return $data;
    }

    protected function removeConditionNecessary($data)
    {
        if (!isset($data['additional_data'])) {
            $data['additional_data'] = $this->getListingProduct()->getAdditionalData();
        }

        if (isset($data['additional_data']['is_need_relist_condition'])) {
            unset($data['additional_data']['is_need_relist_condition']);
        }

        return $data;
    }
}
