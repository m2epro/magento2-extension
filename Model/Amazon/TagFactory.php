<?php

namespace Ess\M2ePro\Model\Amazon;

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
            '8541' => 'The Description data provided for current SKU has technical conflict with data of'
                . ' Amazon Catalog',
            '6024' => 'You are not authorized to list Products using the Brand value you specified',
            '90000900' => 'The attributes are invalid',
            '5000' => 'One of the shipping-related specifics e.g. Weight, Country of Origin, Unit Count etc.,'
                . ' has either an invalid value or format',
            '99001' => 'A value is missing for a required field like Country of Origin, Is expiration dated product,'
                . ' Unit Count or Size etc.',
            '99022' => 'A required attribute field does not have enough values',
            '5665' => 'Request Approval for The brand name you have entered has not been approved by Amazon',
            '8566' => 'SKU does not match any ASIN and the product data provided is not eligible for ASIN creation',
            '5461' => 'You may not create new ASINs for the specified brand or change the brand name on the existing'
                . ' ASIN',
            '90003944' => 'A required Size-related attribute field does not have enough values',
            '8571' => 'Your ability to create new ASINS is temporarily removed because of an unusually high number of'
                . ' ASINs created from your account',
            '20000' => 'A picture failed to be downloaded from the specified URL and cannot be added to this product',
            '90220' => 'A required attribute is not supplied',
            '5995' => 'You may not change the brand name on this ASIN. The brand name currently shown on the ASIN'
                . ' detail page must be used',
            '8005' => 'You are submitting the UPC/EAN value that does not match the identity attribute stored in'
                . ' Amazon catalog for this SKU',
            '8058' => 'The required attributes are missing',
            '8560' => 'Not all of the required specifics are provided or the Product ID is incorrect',
            '20017' => 'A product image is blocked from being uploaded from the specified URL for policy or'
                . ' copyright reasons',
            '6039' => 'Merchant is not authorized to sell products under this restricted product group',
            '90003945' => 'Value provided for a required Size-related attribute field is not valid',
            '8572' => 'You are using UPCs, EANs, ISBNs, ASINs, or JAN codes that do not match the products you'
                . ' are trying to list',
            '20005' => 'An image cannot be associated with this SKU because the SKU was not created due to'
                . ' another error',
            '95021' => 'The Business Price value must be greater than Inventory Price 1',
            '90057' => 'Some of the fields, like Country of Origin or Unit of Measure, contain invalid values',
            '8057' => 'The condition of items that are already being sold on Amazon cannot be changed',
            '99038' => 'Product Description field contains an invalid value',
            '8026' => 'You are not authorized to list products in this category',
            '99036' => 'The Template Name in M2E Shipping Policy doesn’t match one in the Amazon Seller Central',
            '8567' => 'The SKU does not match any ASIN and contains invalid values for attributes required'
                . ' for creation of a new ASIN',
            '90202' => 'You can’t be selling this Amazon Product because of Amazon restriction.'
                . ' You can contact Amazon to be approved for selling this item',
            '8805' => 'All products in a variation family are required to have consistent values,'
                . ' i.e. Brand of a child Product cannot be different from the Parent one',
            '90041' => 'The value for a required attribute is invalid or empty',
            '5561' => 'You may not use trademarked terms in the keywords attribute',
            '90244' => 'invalid value for one of the attributes',
            '8542' => 'The SKU data provided conflicts with the Amazon catalog',
            '99010' => 'A value is missing from one or more required specifics',
            '90225' => 'Value for one of the product attributes is longer than the allowed maximum',
            '99003' => 'A required value is missing for the specified variation theme',
            '96016' => 'An FBA offer already exists on this SKU',
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
