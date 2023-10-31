<?php

namespace Ess\M2ePro\Model\Ebay\Category;

class SpecificValidator
{
    public const ERROR_TAG_CODE = '21919303-m2e';

    /** @var array */
    private $requiredCategorySpecificNames = [];
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay */
    private $ebayCategoryHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Category\Specific\Validation\Result */
    private $validationResultResource;
    /** @var \Ess\M2ePro\Model\Ebay\Category\Specific\Validation\ResultFactory */
    private $resultFactory;
    /** @var \Ess\M2ePro\Model\Ebay\TagFactory */
    private $ebayTagFactory;
    /** @var \Ess\M2ePro\Model\Tag\ListingProduct\Buffer */
    private $tagBuffer;
    /** @var \Ess\M2ePro\Model\TagFactory */
    private $baseTagFactory;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\TagFactory $ebayTagFactory,
        \Ess\M2ePro\Model\Tag\ListingProduct\Buffer $tagBuffer,
        \Ess\M2ePro\Model\TagFactory $baseTagFactory,
        \Ess\M2ePro\Model\Ebay\Category\Specific\Validation\ResultFactory $resultFactory,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Category\Specific\Validation\Result $validationResultResource,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $ebayCategoryHelper
    ) {
        $this->ebayCategoryHelper = $ebayCategoryHelper;
        $this->validationResultResource = $validationResultResource;
        $this->resultFactory = $resultFactory;
        $this->ebayTagFactory = $ebayTagFactory;
        $this->tagBuffer = $tagBuffer;
        $this->baseTagFactory = $baseTagFactory;
    }

    public function validate(\Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct)
    {
        $listingProduct = $ebayListingProduct->getParentObject();

        $validatorResult = $this->getValidatorResult($ebayListingProduct);
        $this->clearErrorTags($listingProduct);

        $customAttributesSpecific = $this->loadCustomSpecific($ebayListingProduct);
        foreach ($customAttributesSpecific as $data) {
            $categoryRequiredNames = $this->loadCategoryRequiredSpecificNames(
                (int)$data['category_id'],
                (int)$data['marketplace_id']
            );

            $attributeCode = $data['attribute_code'];
            $specificName = $data['title'];

            if (!in_array($specificName, $categoryRequiredNames, true)) {
                continue;
            }

            $magentoProduct = $ebayListingProduct->getMagentoProduct();
            $attributeValue = $magentoProduct->getAttributeValue($attributeCode);
            $attributeValue = trim($attributeValue);

            if (empty($attributeValue)) {
                $validatorResult->setInvalidStatus();
                $validatorResult->addErrorMessage(
                    __(
                        'Specific "%specific_name" empty',
                        ['specific_name' => $specificName]
                    )
                );
                $this->addErrorTags($listingProduct);
            }
        }

        $this->flushErrorTags();
        $this->validationResultResource->save($validatorResult);
    }

    /**
     * @return string[]
     */
    private function loadCategoryRequiredSpecificNames(int $categoryId, int $marketplaceId): array
    {
        $key = "{$categoryId}_$marketplaceId";
        if (array_key_exists($key, $this->requiredCategorySpecificNames)) {
            return $this->requiredCategorySpecificNames[$key];
        }

        $specifics = $this->ebayCategoryHelper
            ->getSpecifics($categoryId, $marketplaceId);

        if ($specifics === null) {
            return [];
        }

        $requiredSpecificNames = [];
        foreach ($specifics as $specific) {
            if ($specific['required']) {
                $requiredSpecificNames[] = $specific['title'];
            }
        }

        return $this->requiredCategorySpecificNames[$key] = $requiredSpecificNames;
    }

    public function loadCustomSpecific(\Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct): \Generator
    {
        $category = $ebayListingProduct->getCategoryTemplate();
        foreach ($category->getSpecifics(true) as $specific) {
            if (!$specific->isCustomAttributeValueMode()) {
                continue;
            }

            yield [
                'title' => $specific->getAttributeTitle(),
                'attribute_code' => $specific->getValueCustomAttribute(),
                'category_id' => $category->getCategoryId(),
                'marketplace_id' => $ebayListingProduct->getListing()->getMarketplaceId(),
            ];
        }
    }

    private function getValidatorResult(
        \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct
    ): \Ess\M2ePro\Model\Ebay\Category\Specific\Validation\Result {
        $listingProductId = (int)$ebayListingProduct->getParentObject()->getId();
        $result = $this->resultFactory->create();
        $this->validationResultResource->load(
            $result,
            $listingProductId,
            'listing_product_id'
        );

        if ($result->isObjectNew()) {
            $result->setListingProductId($listingProductId);
        }

        $result->setValidStatus();
        $result->setErrorMessages([]);

        return $result;
    }

    private function addErrorTags(\Ess\M2ePro\Model\Listing\Product $listingProduct): void
    {
        if ($listingProduct->getStatus() !== \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return;
        }

        $tags[] = $this->baseTagFactory->createWithHasErrorCode();
        $tags[] = $this->ebayTagFactory->createByErrorCode(
            self::ERROR_TAG_CODE,
            __('Unable to List Product Due to missing Item Specific(s)')
        );
        $this->tagBuffer->addTags($listingProduct, $tags);
    }

    private function clearErrorTags(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->tagBuffer->removeAllTags($listingProduct);
    }

    private function flushErrorTags(): void
    {
        $this->tagBuffer->flush();
    }
}
