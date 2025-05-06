<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action;

use Ess\M2ePro\Model\Tag\ValidatorIssues;

class TagManager
{
    private \Ess\M2ePro\Model\Tag\ListingProduct\Buffer $tagBuffer;
    private \Ess\M2ePro\Model\TagFactory $baseTagFactory;
    private \Ess\M2ePro\Model\Ebay\TagFactory $ebayTagFactory;

    public function __construct(
        \Ess\M2ePro\Model\TagFactory $baseTagFactory,
        \Ess\M2ePro\Model\Ebay\TagFactory $ebayTagFactory,
        \Ess\M2ePro\Model\Tag\ListingProduct\Buffer $tagBuffer
    ) {
        $this->baseTagFactory = $baseTagFactory;
        $this->ebayTagFactory = $ebayTagFactory;
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

        $tags = [];

        $userErrors = array_filter($messages, function ($message) {
            return $message->getCode() !== ValidatorIssues::NOT_USER_ERROR;
        });

        if (!empty($userErrors)) {
            $tags[] = $this->baseTagFactory->createWithHasErrorCode();

            foreach ($userErrors as $userError) {
                $errorText = $this->mapByCode((string)$userError->getCode());
                if ($errorText === null) {
                    continue;
                }

                $tags[] = $this->ebayTagFactory->createByErrorCode(
                    (string)$userError->getCode(),
                    $errorText
                );
            }

            $this->tagBuffer->addTags($product, $tags);
        }
    }

    public function mapByCode(string $code): ?string
    {
        $map = [
            ValidatorIssues::ERROR_CATEGORY_SETTINGS_NOT_SET => (string)__('Category Settings are not set.'),
            ValidatorIssues::ERROR_QUANTITY_POLICY_CONTRADICTION => (string)__(
                'You’re submitting an item with QTY contradicting the QTY settings in your Selling Policy.'
            ),
            ValidatorIssues::ERROR_CODE_ZERO_QTY => (string)__(
                'You are submitting an Item with zero quantity. It contradicts eBay requirements.'
            ),
            ValidatorIssues::ERROR_CODE_ZERO_QTY_AUTO => (string)__(
                'Cannot submit an Item with zero quantity. It contradicts eBay requirements.'
            ),
            ValidatorIssues::ERROR_INVALID_VARIATIONS => (string)__(
                'Unable to list the product(s) because product variations are assigned incorrectly or missing for
                 the selected Store View.'
            ),
            ValidatorIssues::ERROR_EXCEEDED_VARIATION_ATTRIBUTES => (string)__(
                'The number of Variational Attributes of this Product is out of the eBay Variational Item limits.'
            ),
            ValidatorIssues::ERROR_EXCEEDED_OPTIONS_PER_ATTRIBUTE => (string)__(
                'The number of Options for some Variational Attribute(s) of this Product is out of the eBay
                 Variational Item limits.'
            ),
            ValidatorIssues::ERROR_EXCEEDED_VARIATIONS => (string)__(
                'The Number of Variations of this Magento Product is out of the eBay Variational Item limits.'
            ),
            ValidatorIssues::ERROR_CHANGE_ITEM_TYPE => (string)__(
                'The Product was listed as a Variational Item on eBay. Changing it to a non-Variational Item
                 during revise or relist is not allowed by eBay.'
            ),
            ValidatorIssues::ERROR_BUNDLE_OPTION_VALUE_MISSING => (string)__(
                'Product variation title is missing a value.'
            ),
            ValidatorIssues::ERROR_DUPLICATE_OPTION_VALUES => (string)__(
                'Product variation options contain duplicate values.'
            ),
            ValidatorIssues::ERROR_FIXED_PRICE_BELOW_MINIMUM => (string)__(
                'The Fixed Price must be greater than 0.99.'
            ),
            ValidatorIssues::ERROR_START_PRICE_BELOW_MINIMUM => (string)__(
                'The Start Price must be greater than 0.99.'
            ),
            ValidatorIssues::ERROR_RESERVE_PRICE_BELOW_MINIMUM => (string)__(
                'The Reserve Price must be greater than 0.99.'
            ),
            ValidatorIssues::ERROR_BUY_IT_NOW_PRICE_BELOW_MINIMUM => (string)__(
                'The Buy It Now Price must be greater than 0.99.'
            ),
            ValidatorIssues::ERROR_HIDDEN_STATUS => (string)__(
                'The List action cannot be executed for this Item as it has a Listed (Hidden) status.'
            ),
            ValidatorIssues::ERROR_DUPLICATE_PRODUCT_LISTING => (string)__(
                'There is another Item with the same eBay User ID.'
            ),
        ];

        if (!isset($map[$code])) {
            return null;
        }

        return $map[$code];
    }
}
