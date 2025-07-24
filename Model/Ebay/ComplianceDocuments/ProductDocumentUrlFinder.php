<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\ComplianceDocuments;

use Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductDocumentUrlFinder\Result;
use Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductDocumentUrlFinder\ResultCollection;
use Ess\M2ePro\Model\Ebay\Template\Description as DescriptionTemplate;

class ProductDocumentUrlFinder
{
    private \Ess\M2ePro\Helper\Data $dataHelper;
    private \Ess\M2ePro\Helper\Magento\Attribute $attributeHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Magento\Attribute $attributeHelper
    ) {
        $this->dataHelper = $dataHelper;
        $this->attributeHelper = $attributeHelper;
    }

    public function process(\Ess\M2ePro\Model\Listing\Product $listingProduct): ResultCollection
    {
        $complianceDocumentsSetting = $listingProduct
            ->getChildObject()
            ->getEbayDescriptionTemplate()
            ->getComplianceDocuments();

        if (empty($complianceDocumentsSetting)) {
            return new ResultCollection([]);
        }

        $complianceDocuments = [];

        foreach ($complianceDocumentsSetting as $documentSetting) {
            $mode = (int)$documentSetting['document_mode'];

            $typeAttributeCodeHash = $this->makeHash($documentSetting);
            if ($mode === DescriptionTemplate::COMPLIANCE_DOCUMENTS_MODE_ATTRIBUTE) {
                $complianceDocuments[$typeAttributeCodeHash] = $this
                    ->processAttribute($listingProduct, $documentSetting);
            }

            if ($mode === DescriptionTemplate::COMPLIANCE_DOCUMENTS_MODE_CUSTOM_VALUE) {
                $complianceDocuments[$typeAttributeCodeHash] = $this
                    ->processCustomValue($documentSetting);
            }
        }

        return new ResultCollection(array_values($complianceDocuments));
    }

    private function processCustomValue(array $documentSetting): Result
    {
        $documentUrl = $documentSetting['document_custom_value'];
        $type = $documentSetting['document_type'];
        $languages = $documentSetting['document_languages'] ?? [];

        if (filter_var($documentUrl, FILTER_VALIDATE_URL) === false) {
            $errorMessage = 'Failed to upload the compliance document to eBay: ' .
                'the custom value contains an invalid document URL.';

            return Result::createFail(
                $type,
                $languages,
                $errorMessage
            );
        }

        return Result::createSuccess(
            $type,
            $languages,
            $documentUrl
        );
    }

    private function processAttribute(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        array $documentSetting
    ): Result {
        $type = $documentSetting['document_type'];
        $languages = $documentSetting['document_languages'] ?? [];
        $attributeCode = $documentSetting['document_attribute'];

        $listingProduct->getMagentoProduct()->clearNotFoundAttributes();
        $documentUrl = $listingProduct->getMagentoProduct()->getAttributeValue($attributeCode);
        $notFoundAttributes = $listingProduct->getMagentoProduct()->getNotFoundAttributes();

        if (!empty($notFoundAttributes)) {
            $errorMessage = sprintf(
                'The compliance document was not uploaded on eBay: ' .
                'attribute "%s" was not found in the product',
                $this->attributeHelper->getAttributeLabel($attributeCode)
            );

            return Result::createFail(
                $type,
                $languages,
                $errorMessage
            );
        }

        if (empty($documentUrl)) {
            $errorMessage = sprintf(
                'The compliance document was not uploaded on eBay: ' .
                'attribute "%s" is missing a value',
                $this->attributeHelper->getAttributeLabel($attributeCode)
            );

            return Result::createFail(
                $type,
                $languages,
                $errorMessage
            );
        }

        if (filter_var($documentUrl, FILTER_VALIDATE_URL) === false) {
            $errorMessage = sprintf(
                'The compliance document was not uploaded on eBay: ' .
                'invalid document URL value in attribute "%s"',
                $this->attributeHelper->getAttributeLabel($attributeCode),
            );

            return Result::createFail(
                $type,
                $languages,
                $errorMessage
            );
        }

        return Result::createSuccess(
            $type,
            $languages,
            $documentUrl
        );
    }

    private function makeHash(array $documentSetting): string
    {
        $mode = (int)$documentSetting['document_mode'];
        $hashParts = [$documentSetting['document_type']];

        if ($mode === DescriptionTemplate::COMPLIANCE_DOCUMENTS_MODE_ATTRIBUTE) {
            $hashParts[] = $documentSetting['document_attribute'];
        }

        if ($mode === DescriptionTemplate::COMPLIANCE_DOCUMENTS_MODE_CUSTOM_VALUE) {
            $hashParts[] = $documentSetting['document_custom_value'];
        }

        return $this->dataHelper->md5String(implode('-', $hashParts));
    }
}
