<?php

namespace Ess\M2ePro\Model\Ebay;

class TagFactory
{
    /** @var \Ess\M2ePro\Model\TagFactory */
    private $tagFactory;

    public function __construct(\Ess\M2ePro\Model\TagFactory $tagFactory)
    {
        $this->tagFactory = $tagFactory;
    }

    public function createByErrorCode(string $errorCode, string $text): \Ess\M2ePro\Model\Tag
    {
        $text = $this->getPreparedText($errorCode) ?? $this->trimText($text);

        return $this->tagFactory->create($errorCode, $text);
    }

    private function getPreparedText(string $errorCode): ?string
    {
        $map = [
            '21919303' => 'Required Item Specifics are missing',
            '240' => 'The title or description may contain improper words',
            '21920061' => 'The selected attribute is not allowed as a variation specific',
            '17' => 'This item cannot be accessed',
            '197' => 'To list in 2 categories, 2 different categories must be specified',
            '21919302' => 'UPC/EAN has an invalid value',
            '70' => 'Listing titles exceed the limit of "80" characters',
            '21919490' => 'HTTP resources are not allowed, must be updated or removed',
            '21919188' => 'You\'ve reached the number of items you can list',
            '21916328' => 'Invalid return policy',
            '17000' => 'Lot Size is invalid',
            '21916799' => 'SKU does not exist in Non-ManageBySKU item specified by ItemID',
            '106' => 'A description is required',
            '34' => 'Primary Category is missing',
            '69' => 'Title is missing',
            '73' => 'The Price is invalid, or below the minimum',
            '21919137' => 'Photo resolution is low',
            '21919308' => 'Item Specific value must be not more than 65 characters',
            '36' => 'An error occurred while processing your request',
            '21916664' => 'Variation Specifics provided does not match with the variation specifics on the item',
            '21919067' => 'This listing is a duplicate of an item you already have on eBay',
            '21916635' => 'A single-SKU item cannot be changed to be a multi-SKU item during relist (or revise)',
            '231' => 'The Item is either not active or no longer available in eBay database',
            '22003' => 'Auto decline amount cannot be greater than or equal to the Buy It Now price',
            '21916883' => 'The provided condition is invalid for the selected primary category',
            '21555' => 'A valid category must be specified',
            '23004' => 'The Best Offer Auto Accept Price must be less than the Buy It Now price',
            '21919485' => 'The selected shipping rate table was not found for this eBay site',
            '21916311' => 'StartPrice or Quantity must be provided for the item',
            '37' => 'The value set for one of the fields like Category ID, Item Condition, Image attribute, etc. is '
                . 'missing or invalid',
            '21916543' => 'The server specified by ExternalPictureURL is not responding',
            '21916591' => 'A variation could not be found for one of the SKUs',
            '21916587' => 'Variation Titles/Options may not correspond with the original values or may not meet the '
                . 'channel requirements',
            '21916639' => 'Provided variation specific (e.g. Size, Color) does not match the variation specific used '
                . 'for pictures on the Channel',
            '21916638' => 'The variation specific used for pictures on the Channel has been removed. A relevant '
                . 'variation picture set must be sent',
            '21916250' => 'A return option is missing or not valid',
            '21916736' => 'Variation level SKU or Variation level SKU and ItemID should be supplied to revise '
                . 'a Multi-SKU item',
            '107' => 'The category is not valid',
            '21916750' => 'You are not allowed to revise an ended item',
            '21916274' => 'The SKU-based inventory model listing cannot be relisted using Item ID instead',
            '21916364' => 'Shipping service cost is not valid',
            '21916583' => 'Variation is required for a listing that previously had variations',
            '21916260' => 'Postage service cost exceeds the maximum allowed for selected category',
            '55' => 'Your feedback comment for this user was not left',
            '291' => 'You are not allowed to revise ended auction listings',
            '20004' => 'A mixture of Self Hosted and EPS pictures are not allowed',
            '20822' => 'Invalid Item ID or Transaction ID',
            '219021' => 'Package weight is over the weight limit for the shipping service',
            '21919456' => 'To manage Shipping and Return info in M2E Pro, opt-out of eBay Business policies.'
                . ' Go to My eBay > Account > Manage your business policies and click opt-out',
        ];

        return $map[$errorCode] ?? null;
    }

    private function trimText(string $text): string
    {
        if (strlen($text) <= 255) {
            return $text;
        }

        return substr($text, 0, 252) . '...';
    }
}
