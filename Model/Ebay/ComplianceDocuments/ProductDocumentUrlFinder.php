<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\ComplianceDocuments;

use Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductDocumentUrlFinder\Result;
use Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductDocumentUrlFinder\ResultCollection;

class ProductDocumentUrlFinder
{
    private \Ess\M2ePro\Helper\Data $dataHelper;

    public function __construct(\Ess\M2ePro\Helper\Data $dataHelper)
    {
        $this->dataHelper = $dataHelper;
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
            $type = $documentSetting['document_type'];
            $attributeCode = $documentSetting['document_attribute'];

            $typeAttributeCodeHash = $this->dataHelper->md5String($type . '-' . $attributeCode);

            $listingProduct->getMagentoProduct()->clearNotFoundAttributes();
            $documentUrl = $listingProduct->getMagentoProduct()->getAttributeValue($attributeCode);
            $notFoundAttributes = $listingProduct->getMagentoProduct()->getNotFoundAttributes();

            if ($notFoundAttributes !== []) {
                $errorMessage = sprintf(
                    'The compliance document was not uploaded on eBay: ' .
                    'attribute "%s" was not found in the product',
                    $attributeCode
                );
                $complianceDocuments[$typeAttributeCodeHash] = Result::createFail(
                    $type,
                    $attributeCode,
                    $errorMessage
                );

                continue;
            }

            if (empty($documentUrl)) {
                $errorMessage = sprintf(
                    'The compliance document was not uploaded on eBay: ' .
                    'attribute "%s" is missing a value',
                    $attributeCode
                );
                $complianceDocuments[$typeAttributeCodeHash] = Result::createFail(
                    $type,
                    $attributeCode,
                    $errorMessage
                );

                continue;
            }

            if (filter_var($documentUrl, FILTER_VALIDATE_URL) === false) {
                $errorMessage = sprintf(
                    'The compliance document was not uploaded on eBay: ' .
                    'invalid document URL value in attribute "%s"',
                    $attributeCode,
                );
                $complianceDocuments[$typeAttributeCodeHash] = Result::createFail(
                    $type,
                    $attributeCode,
                    $errorMessage
                );
                continue;
            }

            $complianceDocuments[$typeAttributeCodeHash] = Result::createSuccess(
                $type,
                $attributeCode,
                $documentUrl
            );
        }

        return new ResultCollection(array_values($complianceDocuments));
    }
}
