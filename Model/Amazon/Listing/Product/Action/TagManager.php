<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action;

use Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Validator;

class TagManager
{
    private \Ess\M2ePro\Model\Tag\ListingProduct\Buffer $tagBuffer;
    private \Ess\M2ePro\Model\TagFactory $baseTagFactory;
    private \Ess\M2ePro\Model\Amazon\TagFactory $amazonTagFactory;

    public function __construct(
        \Ess\M2ePro\Model\TagFactory $baseTagFactory,
        \Ess\M2ePro\Model\Amazon\TagFactory $amazonTagFactory,
        \Ess\M2ePro\Model\Tag\ListingProduct\Buffer $tagBuffer
    ) {
        $this->baseTagFactory = $baseTagFactory;
        $this->amazonTagFactory = $amazonTagFactory;
        $this->tagBuffer = $tagBuffer;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $product
     * @param \Ess\M2ePro\Model\Connector\Connection\Response\Message[] $messages
     */
    public function addErrorTags(\Ess\M2ePro\Model\Listing\Product $product, array $messages): void
    {
        if (empty($messages)) {
            return;
        }

        $errorMessages = array_filter($messages, static function ($message) {
            return $message->isError();
        });

        if (empty($errorMessages)) {
            return;
        }

        $tags = [];

        $userErrors = array_filter($errorMessages, function ($message) {
            return $message->getCode() !== \Ess\M2ePro\Model\Tag\ValidatorIssues::NOT_USER_ERROR;
        });

        if (!empty($userErrors)) {
            $tags[] = $this->baseTagFactory->createWithHasErrorCode();

            foreach ($userErrors as $userError) {
                $errorText = $this->mapByCode((string)$userError->getCode());
                if ($errorText === null) {
                    continue;
                }

                $tags[] = $this->amazonTagFactory->createByErrorCode(
                    (string)$userError->getCode(),
                    $errorText
                );
            }

            $this->tagBuffer->addTags($product, $tags);
            $this->tagBuffer->flush();
        }
    }

    public function mapByCode(string $code): ?string
    {
        $map = [
            Validator::ERROR_ITEM_BLOCKED_ON_AMAZON => (string)__(
                'The Action can not be executed as the Item was Closed, Incomplete or Blocked on Amazon.'
            ),
            \Ess\M2ePro\Model\Tag\ValidatorIssues::ERROR_QUANTITY_POLICY_CONTRADICTION => (string)__(
                'The quantity submitted for this item conflicts with the quantity settings defined
                 in your Selling Policy.'
            ),
            \Ess\M2ePro\Model\Tag\ValidatorIssues::ERROR_CODE_ZERO_QTY => (string)__(
                'You are submitting an Item with zero quantity. It contradicts Amazon requirements.'
            ),
            Validator::ERROR_CODE_ZERO_PRICE => (string)__(
                'The Price must be greater than 0.'
            ),
            Validator::ERROR_CODE_ZERO_BUSINESS_PRICE => (string)__(
                'The Business Price must be greater than 0.'
            ),
            Validator::ERROR_PARENT_HAS_NO_CHILD => (string)__(
                'This parent product has no associated child products eligible for the selected action.'
            ),
            Validator::ERROR_DIFFERENT_ACTIONS_CHILD_PRODUCTS => (string)__(
                'The action cannot be completed because some Child Products are currently
                 undergoing different actions.'
            ),
            Validator::ERROR_NOT_PHYSICAL_PRODUCT => (string)__(
                'Only physical Products can be processed.'
            ),
            Validator::VARIATION_MAGENTO_NOT_SELECTED => (string)__('Magento Variation is not selected.'),
            Validator::VARIATION_CHANNEL_NOT_SELECTED => (string)__('Channel Variation is not selected.'),
            Validator::ERROR_REQUIRE_MANUAL_ASIN_SEARCH => (string)__(
                'This product cannot be listed. For Bundles, Custom Option Simples, or Downloadables
                 with separate links, the ASIN/ISBN must be found manually.'
            ),
            Validator::ERROR_INVALID_GENERAL_ID => (string)__(
                'The ASIN/ISBN value entered is invalid.'
            ),
            Validator::ERROR_GENERAL_ID_NOT_FOUND => (string)__(
                'The provided Product Identifier could not be found on Amazon'
            ),
            Validator::ERROR_MULTIPLE_PRODUCTS_FOUND_GENERAL_ID => (string)__(
                'Multiple products were found on Amazon using the provided Product Identifier.'
            ),
            Validator::ERROR_PARENT_FOUND_CHILD_EXPECTED_GENERAL_ID => (string)__(
                'Parent Product could not be found on Amazon using the provided Product Identifier.'
            ),
            Validator::ERROR_AMAZON_RESTRICTIONS_BY_GENERAL_ID => (string)__(
                'Work with Amazon Parent Product found via Product Identifier
                 search is limited due to API restrictions.'
            ),
            Validator::ERROR_VARIATION_ATTRIBUTES_MISMATCH_GENERAL_ID => (string)__(
                'The number of Variation attributes of the Amazon and Magento Parent Products do not match.'
            ),
            Validator::ERROR_CHILD_FOUND_PARENT_EXPECTED => (string)__(
                'A Simple or Child product was found, but a Parent ASIN/ISBN is required.'
            ),
            Validator::ERROR_INVALID_WORLDWIDE_ID => (string)__(
                'The UPC/EAN value entered is invalid.'
            ),
            Validator::ERROR_WORLDWIDE_ID_NOT_FOUND => (string)__(
                'No ASIN found on Amazon for the provided UPC/EAN.'
            ),
            Validator::ERROR_MULTIPLE_PRODUCTS_FOUND_WORLDWIDE_ID => (string)__(
                'Multiple products were found on Amazon using the provided UPC/EAN.'
            ),
            Validator::ERROR_PARENT_FOUND_CHILD_EXPECTED_WORLDWIDE_ID => (string)__(
                'A Parent Product was found using the provided UPC/EAN,
                 but a Simple or Child ASIN/ISBN is required.'
            ),
            Validator::ERROR_NOT_CHILD => (string)__(
                'The Product found on Amazon for the provided UPC/EAN is not a Child Product.'
            ),
            Validator::ERROR_CHILD_RELATED_TO_ANOTHER_PARENT => (string)__(
                'No Product could be found on Amazon for the provided UPC/EAN.'
            ),
            Validator::ERROR_NO_CHILD_WITH_VARIATION_MATCH => (string)__(
                'The Product was found on Amazon using the specified UPC/EAN, but its Parent
                 has no Child Product matching the required Variation attributes.'
            ),
            Validator::ERROR_IDENTIFIER_MISSING => (string)__(
                'Product cannot be listed because ASIN/ISBN is not assigned,
                 and the UPC/EAN is not provided or invalid.'
            ),
            Validator::ERROR_PRODUCT_TYPE_MISSING => (string)__(
                'Product cannot be listed because the new ASIN/ISBN creation process has started,
                 but the Product Type is not specified.'
            ),
            Validator::ERROR_VARIATION_THEME_MISSING => (string)__(
                'Product cannot be listed because the new ASIN/ISBN creation process has started,
                 but the Variation theme is not specified.'
            ),
            Validator::ERROR_INVALID_IDENTIFIER => (string)__(
                'The UPC/EAN value entered has invalid format.'
            ),
            Validator::ERROR_CREATE_IDENTIFIER_FAILED => (string)__(
                'A new ASIN/ISBN cannot be created because the provided UPC/EAN already exists on Amazon.'
            ),
            Validator::ERROR_FBA_ITEM_LIST => (string)__(
                'Cannot List FBA Items because their quantity is unknown.'
            ),
            Validator::PRODUCT_TYPE_INVALID => (string)__(
                'To list a new ASIN/ISBN on Amazon, please assign a valid Product Type.'
            ),
            Validator::ITEM_CONDITION_NOT_SPECIFIED => (string)__(
                'Product can not be Listed, Item Condition is not specified.'
            ),
            Validator::PARENT_NOT_LINKED => (string)__(
                'This Product can not be Listed, its Parent is not linked to an Amazon Parent Product.'
            ),
            Validator::PRODUCT_MISSING_LINK_OR_NEW_IDENTIFIER => (string)__(
                'The Product cannot be listed. It must either be linked to an existing
                 Amazon Product or prepared for new ASIN/ISBN creation.'
            ),
            Validator::ERROR_SKU_ALREADY_PROCESSING => (string)__(
                'Another Product with the same SKU is being Listed simultaneously with this one.'
            ),
            Validator::ERROR_DUPLICATE_SKU_LISTING => (string)__(
                'Product with the same SKU exists in another M2E Pro Listing
                 created under the same Merchant ID and marketplace.'
            ),
            Validator::ERROR_DUPLICATE_SKU_UNMANAGED => (string)__(
                'Product with the same SKU is found in M2E Pro Unmanaged Listing.'
            ),
            Validator::ERROR_SKU_MISSING => (string)__(
                'SKU is not provided.'
            ),
            Validator::ERROR_SKU_LENGTH_EXCEEDED => (string)__(
                'The length of SKU must be less than 40 characters.'
            ),
            Validator::ERROR_SKU_ASSIGN_PARENT_CHILD_EXPECTED => (string)__(
                'Product can not be Listed, the SKU is assigned to a Parent, not the Child or Simple Product.'
            ),
            Validator::ERROR_SKU_ASSIGNED_TO_DIFFERENT_ASIN => (string)__(
                'Product can not be Listed, the SKU is assigned to the Product with different ASIN/ISBN.'
            ),
            Validator::ERROR_SKU_ASSIGN_CHILD_PARENT_EXPECTED => (string)__(
                'Product can not be Listed, the SKU is assigned to a Child or Simple Product, not the Parent.'
            ),
            Validator::ERROR_PARENT_LISTING_API_RESTRICTION => (string)__(
                'Product can not be Listed because work with the Product found via
                 Product Identifier search is limited due to API restrictions.'
            ),
            Validator::ERROR_VARIATION_ATTRIBUTES_MISMATCH_BY_SKU => (string)__(
                'Product can not be Listed because the number of Variation attributes
                 of the Amazon and Magento Parent Products do not match.'
            ),
            Validator::ERROR_NOT_CHILD_BY_SKU => (string)__(
                'Product cannot be Listed because Product found on Amazon is not a Child Product.'
            ),
            Validator::ERROR_CHILD_PARENT_API_RESTRICTION => (string)__(
                'Product cannot be Listed because the item found by SKU is a Child of a Parent Product
                 restricted by Amazon\'s API.'
            ),
            Validator::ERROR_SKU_RELATED_TO_ANOTHER_PARENT_IDENTIFIER => (string)__(
                'Product cannot be Listed because the SKU is assigned to a Child
                 of a different Amazon Parent Product with another ASIN/ISBN.'
            ),
            Validator::ERROR_SKU_RELATED_TO_ANOTHER_IDENTIFIER => (string)__(
                'Product cannot be Listed because the SKU is assigned to an Amazon Product with another ASIN/ISBN.'
            ),
            Validator::ERROR_NO_CHILD_WITH_ATTRIBUTES_MATCH => (string)__(
                'Product cannot be Listed because its Parent has no Child Product with
                 the required Variation attribute combination.'
            ),
            Validator::ERROR_IDENTIFIER_ALREADY_USED_FOR_ANOTHER_PRODUCT => (string)__(
                'Product cannot be Listed because the ASIN/ISBN found by SKU
                 is already linked to another Magento product.'
            ),
            Validator::ERROR_FBA_ITEM_RELIST => (string)__(
                'Cannot Relist FBA Items because their quantity is managed by Amazon.'
            ),
            Validator::ERROR_CANNOT_SWITCH_FULFILLMENT_NO_QTY_FEED => (string)__(
                'Fulfillment mode cannot be changed because quantity updates are not allowed.'
            ),
            Validator::FULFILLMENT_ALREADY_APPLIED => (string)__(
                'Fulfillment method cannot be changed because it is currently in use.'
            ),
            Validator::ERROR_FBA_ITEM_STOP => (string)__(
                'Cannot Stop FBA Items because their quantity is managed by Amazon.'
            ),
        ];

        if (!isset($map[$code])) {
            return null;
        }

        return $map[$code];
    }
}
