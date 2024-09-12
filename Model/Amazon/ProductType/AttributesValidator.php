<?php

namespace Ess\M2ePro\Model\Amazon\ProductType;

class AttributesValidator
{
    public const ERROR_TAG_CODE = '99001-m2e';

    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\ProductType\Validation */
    private $productTypeValidationResource;
    /** @var \Ess\M2ePro\Model\Amazon\ProductType\ValidationFactory */
    private $productTypeValidationFactory;
    /** @var \Ess\M2ePro\Model\Tag\ListingProduct\Buffer */
    private $tagBuffer;
    /** @var \Ess\M2ePro\Model\Amazon\TagFactory */
    private $amazonTagFactory;
    /** @var \Ess\M2ePro\Model\TagFactory */
    private $baseTagFactory;
    private \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $templateProductTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $templateProductTypeRepository,
        \Ess\M2ePro\Model\ResourceModel\Amazon\ProductType\Validation $productTypeValidationResource,
        \Ess\M2ePro\Model\Amazon\ProductType\ValidationFactory $productTypeValidationFactory,
        \Ess\M2ePro\Model\Tag\ListingProduct\Buffer $tagBuffer,
        \Ess\M2ePro\Model\Amazon\TagFactory $amazonTagFactory,
        \Ess\M2ePro\Model\TagFactory $baseTagFactory
    ) {
        $this->productTypeValidationResource = $productTypeValidationResource;
        $this->productTypeValidationFactory = $productTypeValidationFactory;
        $this->tagBuffer = $tagBuffer;
        $this->amazonTagFactory = $amazonTagFactory;
        $this->baseTagFactory = $baseTagFactory;
        $this->templateProductTypeRepository = $templateProductTypeRepository;
    }

    public function validate(
        \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct,
        int $productTypeId
    ): void {
        $resource = $this->productTypeValidationResource;
        $validationResult = $this->productTypeValidationFactory->create();
        $resource->load(
            $validationResult,
            $amazonListingProduct->getListingProductId(),
            'listing_product_id'
        );

        if ($validationResult->isObjectNew()) {
            $validationResult->setListingProductId($amazonListingProduct->getListingProductId());
        }

        $validationResult->setValidStatus();
        $validationResult->setErrorMessages([]);

        try {
            $productType = $this->templateProductTypeRepository->get($productTypeId);
        } catch (\Ess\M2ePro\Model\Exception\EntityNotFound $exception) {
            $validationResult->setInvalidStatus();
            $validationResult->addErrorMessage(__('Product Type not found'));

            $resource->save($validationResult);

            return;
        }

        $magentoProduct = $amazonListingProduct->getActualMagentoProduct();
        $listingProduct = $amazonListingProduct->getParentObject();

        $this->removeErrorTags($listingProduct);

        foreach ($productType->getCustomAttributesList() as $customAttribute) {
            $path = $customAttribute['name'];
            try {
                $validator = $productType->getDictionary()->getValidatorByPath($path);
            } catch (\Ess\M2ePro\Model\Exception\Logic $e) {
                $validationResult->addErrorMessage('WARNING! ' . $e->getMessage());
                continue;
            }

            if (!$validator->isRequiredSpecific()) {
                continue;
            }

            $attributeCode = $customAttribute['attribute_code'];
            $attributeValue = $magentoProduct->getAttributeValue($attributeCode);
            $isAttributeValid = $validator->validate($attributeValue);

            if (!$isAttributeValid) {
                $validationResult->setInvalidStatus();
                foreach ($validator->getErrors() as $errorMessage) {
                    $validationResult->addErrorMessage($errorMessage);
                }
            }
        }

        if (!$validationResult->isValid()) {
            $this->addErrorTags($listingProduct);
        }

        $validationResult->touchCreateDate();
        $validationResult->touchUpdateDate();
        $resource->save($validationResult);
    }

    private function addErrorTags(\Ess\M2ePro\Model\Listing\Product $listingProduct): void
    {
        $tags[] = $this->baseTagFactory->createWithHasErrorCode();
        $tags[] = $this->amazonTagFactory->createByErrorCode(
            self::ERROR_TAG_CODE,
            __('Unable to List Product Due to Invalid Attribute Value(s)')
        );
        $this->tagBuffer->addTags($listingProduct, $tags);
        $this->tagBuffer->flush();
    }

    private function removeErrorTags(\Ess\M2ePro\Model\Listing\Product $listingProduct): void
    {
        $this->tagBuffer->removeAllTags($listingProduct);
        $this->tagBuffer->flush();
    }
}
