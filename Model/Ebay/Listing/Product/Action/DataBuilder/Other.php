<?php

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder;

class Other extends AbstractModel
{
    private \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $componentEbayCategoryEbay;
    private \Ess\M2ePro\Model\Ebay\Video\ProductProcessor $videoProductProcessor;
    private \Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductProcessor $complianceDocumentsProcessor;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Video\ProductProcessor $videoProductProcessor,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $componentEbayCategoryEbay,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductProcessor $complianceDocumentsProcessor,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->componentEbayCategoryEbay = $componentEbayCategoryEbay;
        $this->videoProductProcessor = $videoProductProcessor;
        $this->complianceDocumentsProcessor = $complianceDocumentsProcessor;
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getBuilderData(): array
    {
        $data = array_merge(
            $this->getConditionData(),
            $this->getConditionNoteData(),
            $this->getVatTaxData(),
            $this->getLotSizeData(),
            $this->getPaymentData(),
            $this->getPriceDiscountMapData(),
            $this->getVideoIdData(),
            $this->getComplianceDocumentsData(),
        );

        return $data;
    }

    private function getConditionData(): array
    {
        $this->searchNotFoundAttributes();
        $source = $this->getEbayListingProduct()
                       ->getDescriptionTemplateSource();

        $condition = $source->getCondition();

        if (!$this->processNotFoundAttributes('Condition')) {
            return [];
        }

        $result = [
            'item_condition' => $condition,
        ];

        $descriptors = $this->getConditionDescriptors($source);
        if (!empty($descriptors)) {
            $result['item_condition_descriptors'] = $descriptors;
        }

        return $result;
    }

    private function getConditionDescriptors(\Ess\M2ePro\Model\Ebay\Template\Description\Source $source): array
    {
        $this->searchNotFoundAttributes();

        $conditionDescriptors = $source->getConditionDescriptors();
        foreach ($conditionDescriptors['not_found_attributes'] as $attribute) {
            $this->processNotFoundAttributes($attribute);
        }

        $descriptors = [];
        foreach ($conditionDescriptors['required_descriptors'] as $descriptorId => $gradeId) {
            $descriptors[] = [
                'name' => (string)$descriptorId,
                'value' => (string)$gradeId,
            ];
        }

        foreach ($conditionDescriptors['optional_descriptors'] as $descriptorId => $gradeVal) {
            $descriptors[] = [
                'name' => (string)$descriptorId,
                'value' => null,
                'additional_info' => $gradeVal,
            ];
        }

        return $descriptors;
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getConditionNoteData(): array
    {
        $this->searchNotFoundAttributes();
        $data = $this->getEbayListingProduct()->getDescriptionTemplateSource()->getConditionNote();
        $this->processNotFoundAttributes('Seller Notes');

        return [
            'item_condition_note' => $data,
        ];
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getVatTaxData(): array
    {
        $data = [
            'tax_category' => $this->getEbayListingProduct()->getSellingFormatTemplateSource()->getTaxCategory(),
        ];

        if ($this->getEbayMarketplace()->isVatEnabled()) {
            $data['vat_mode'] = (int)$this->getEbayListingProduct()->getEbaySellingFormatTemplate()->isVatModeEnabled();
            $data['vat_percent'] = $this->getEbayListingProduct()->getEbaySellingFormatTemplate()->getVatPercent();
        }

        if ($this->getEbayMarketplace()->isTaxTableEnabled()) {
            $data['use_tax_table'] = $this->getEbayListingProduct()
                                          ->getEbaySellingFormatTemplate()
                                          ->isTaxTableEnabled();
        }

        return $data;
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getLotSizeData(): array
    {
        $categoryFeatures = $this->componentEbayCategoryEbay->getFeatures(
            $this->getEbayListingProduct()->getCategoryTemplateSource()->getCategoryId(),
            $this->getMarketplace()->getId()
        );

        if (!isset($categoryFeatures['lsd']) || $categoryFeatures['lsd'] == 1) {
            return [];
        }

        return [
            'lot_size' => $this->getEbayListingProduct()->getSellingFormatTemplateSource()->getLotSize(),
        ];
    }

    /**
     * @return array[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getPaymentData(): array
    {
        $payPalImmediatePayment = $this->getEbayListingProduct()
                                       ->getEbaySellingFormatTemplate()
                                       ->getPayPalImmediatePayment();

        return [
            'payment' => [
                'paypal' => [
                    'immediate_payment' => $payPalImmediatePayment,
                ],
            ],
        ];
    }

    private function getPriceDiscountMapData(): array
    {
        if (
            !$this->getEbayListingProduct()->isListingTypeFixed() ||
            !$this->getEbayListingProduct()->isPriceDiscountMap()
        ) {
            return [];
        }

        $data = [
            'minimum_advertised_price' => $this->getEbayListingProduct()->getPriceDiscountMap(),
        ];

        $exposure = $this->getEbayListingProduct()->getEbaySellingFormatTemplate()->getPriceDiscountMapExposureType();
        $data['minimum_advertised_price_exposure'] =
            \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\Price::getPriceDiscountMapExposureType($exposure);

        return ['price_discount_map' => $data];
    }

    private function getVideoIdData(): array
    {
        $this->collectVideoWarningMessages();

        if (
            !$this->getEbayListingProduct()->hasVideoId()
            && !$this->getEbayListingProduct()->hasOnlineVideoId()
        ) {
            return [];
        }

        $videoId = $this->getEbayListingProduct()->hasVideoId()
            ? $this->getEbayListingProduct()->getVideoId()
            : '';

        return [
            'product_details' => [
                'video_id' => $videoId,
            ],
        ];
    }

    private function collectVideoWarningMessages(): void
    {
        $result = $this->videoProductProcessor->process($this->getListingProduct());

        if ($result->isFail()) {
            $this->addWarningMessage($result->getFailMessage());
        }

        if ($result->isInProgress()) {
            $message = __(
                'The upload of the product video is currently underway. It may take some time before ' .
                'the video is fully processed and available on the channel.'
            );
            $this->addWarningMessage($message);
        }
    }

    private function getComplianceDocumentsData(): array
    {
        $this->collectDocumentsWarningMessages();

        $complianceDocuments = $this->getEbayListingProduct()->getComplianceDocuments();
        $onlineComplianceDocuments = $this->getEbayListingProduct()->getOnlineComplianceDocuments();

        if (
            empty($complianceDocuments)
            && empty($onlineComplianceDocuments)
        ) {
            return [];
        }

        $metadataDocuments = [];
        foreach ($complianceDocuments as $document) {
            $metadataDocuments[] = [
                'type' => $document['type'],
                'document_id' => $document['document_id'],
            ];
        }

        $this->addMetaData('compliance_documents', $metadataDocuments);

        return [
            'regulatory' => [
                'documents' => array_column($metadataDocuments, 'document_id'),
            ],
        ];
    }

    private function collectDocumentsWarningMessages(): void
    {
        $resultCollection = $this->complianceDocumentsProcessor->process($this->getListingProduct(), false);

        foreach ($resultCollection->getResults() as $processorResult) {
            if ($processorResult->isFail()) {
                $this->addWarningMessage($processorResult->getFailMessage());
            }

            if ($processorResult->isInProgress()) {
                $message = __(
                    'The upload of the product documents is currently underway. It may take some time before ' .
                    'the documents is fully processed and available on the channel.'
                );
                $this->addWarningMessage($message);
            }
        }
    }
}
