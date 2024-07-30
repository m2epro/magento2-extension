<?php

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\ListAction;

class Response extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Response
{
    private \Ess\M2ePro\Model\Ebay\Video\ProductProcessor $videoProductProcessor;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Video\ProductProcessor $videoProductProcessor,
        \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataHasher $dataHasher,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $componentEbayCategoryEbay,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct(
            $dataHasher,
            $componentEbayCategoryEbay,
            $activeRecordFactory,
            $helperFactory,
            $modelFactory
        );

        $this->videoProductProcessor = $videoProductProcessor;
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

        $data = $this->appendItemFeesValues($data, $response);
        $data = $this->appendStartDateEndDateValues($data, $response);
        $data = $this->appendGalleryImagesValues($data, $response);

        $data = $this->appendSpecificsReplacementValues($data);
        $data = $this->appendWithoutVariationMpnIssueFlag($data);
        $data = $this->appendIsVariationMpnFilledValue($data);

        $data = $this->appendIsVariationValue($data);
        $data = $this->appendIsAuctionType($data);

        $data = $this->appendDescriptionValues($data);
        $data = $this->appendImagesValues($data);
        $data = $this->appendProductIdentifiersValues($data);
        $data = $this->appendCategoriesValues($data);
        $data = $this->appendPartsValues($data);
        $data = $this->appendShippingValues($data);
        $data = $this->appendReturnValues($data);
        $data = $this->appendOtherValues($data);
        $data = $this->appendBestOfferValue($data);

        if (isset($data['additional_data'])) {
            $data['additional_data'] = \Ess\M2ePro\Helper\Json::encode($data['additional_data']);
        }

        $this->getListingProduct()->addData($data);
        $this->getListingProduct()->getChildObject()->addData($data);
        $this->getListingProduct()->save();

        $this->updateVariationsValues(false);

        $this->videoProductProcessor->process($this->getListingProduct());
    }

    protected function appendSpecificsReplacementValues($data)
    {
        if (!isset($data['additional_data'])) {
            $data['additional_data'] = $this->getListingProduct()->getAdditionalData();
        }

        $tempKey = 'variations_specifics_replacements';
        unset($data['additional_data'][$tempKey]);

        $requestMetaData = $this->getRequestMetaData();
        if (!isset($requestMetaData[$tempKey])) {
            return $data;
        }

        $data['additional_data'][$tempKey] = $requestMetaData[$tempKey];

        return $data;
    }

    protected function appendWithoutVariationMpnIssueFlag($data)
    {
        $requestData = $this->getRequestData()->getData();
        if (empty($requestData['without_mpn_variation_issue'])) {
            return $data;
        }

        if (!isset($data['additional_data'])) {
            $data['additional_data'] = $this->getListingProduct()->getAdditionalData();
        }

        $data['additional_data']['without_mpn_variation_issue'] = true;

        return $data;
    }
}
