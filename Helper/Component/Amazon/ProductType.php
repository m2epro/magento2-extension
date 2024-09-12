<?php

namespace Ess\M2ePro\Helper\Component\Amazon;

class ProductType
{
    public const SPECIFIC_KEY_NAME = 'item_name#array/value';
    public const SPECIFIC_KEY_BRAND = 'brand#array/value';
    public const SPECIFIC_KEY_MANUFACTURER = 'manufacturer#array/value';
    public const SPECIFIC_KEY_DESCRIPTION = 'product_description#array/value';
    public const SPECIFIC_KEY_COUNTRY_OF_ORIGIN = 'country_of_origin#array/value';
    public const SPECIFIC_KEY_ITEM_PACKAGE_WEIGHT = 'item_package_weight#array/value';
    public const SPECIFIC_KEY_MAIN_PRODUCT_IMAGE_LOCATOR = 'main_product_image_locator#array/media_location';
    public const SPECIFIC_KEY_MAIN_OFFER_IMAGE_LOCATOR = 'main_offer_image_locator#array/media_location';
    public const SPECIFIC_KEY_OTHER_OFFER_IMAGE_LOCATOR = 'other_offer_image_locator_1#array/media_location';
    public const SPECIFIC_KEY_BULLET_POINT = 'bullet_point#array/value';

    public static function getTimezoneShift(): int
    {
        $dateLocal = \Ess\M2ePro\Helper\Date::createDateInCurrentZone('2022-01-01');
        $dateUTC = \Ess\M2ePro\Helper\Date::createDateGmt($dateLocal->format('Y-m-d H:i:s'));

        return $dateUTC->getTimestamp() - $dateLocal->getTimestamp();
    }

    public static function getMainImageSpecifics(): array
    {
        return [
            self::SPECIFIC_KEY_MAIN_PRODUCT_IMAGE_LOCATOR,
            self::SPECIFIC_KEY_MAIN_OFFER_IMAGE_LOCATOR,
        ];
    }

    public static function getOtherImagesSpecifics(): array
    {
        return [
            'other_product_image_locator_1#array/media_location',
            'other_offer_image_locator_1#array/media_location',
        ];
    }

    public static function getRecommendedBrowseNodesLink(int $marketplaceId): string
    {
        $map = [
            \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_UK
                => 'https://sellercentral.amazon.co.uk/help/hub/reference/G201742570',
            \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_IT
                => 'https://sellercentral.amazon.it/help/hub/reference/G201742570',
            \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_FR
                => 'https://sellercentral.amazon.fr/help/hub/reference/G201742570',
            \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_DE
                => 'https://sellercentral.amazon.de/help/hub/reference/G201742570',
            \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_ES
                => 'https://sellercentral.amazon.es/help/hub/reference/G201742570',
        ];

        if (!array_key_exists($marketplaceId, $map)) {
            return '';
        }

        return (string)__(
            '<a style="display: block; margin-top: -10px" href="%url">View latest Browse Node ID List</a>',
            ['url' => $map[$marketplaceId]]
        );
    }
}
