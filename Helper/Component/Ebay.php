<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component;

use Ess\M2ePro\Model\Listing\Product as ListingProduct;

class Ebay
{
    public const NICK = 'ebay';

    public const MARKETPLACE_SYNCHRONIZATION_LOCK_ITEM_NICK = 'ebay_marketplace_synchronization';

    public const MARKETPLACE_US = 1;
    public const MARKETPLACE_CA = 2;
    public const MARKETPLACE_UK = 3;
    public const MARKETPLACE_AU = 4;
    public const MARKETPLACE_BE_FR = 6;
    public const MARKETPLACE_FR = 7;
    public const MARKETPLACE_DE = 8;
    public const MARKETPLACE_MOTORS = 9;
    public const MARKETPLACE_IT = 10;
    public const MARKETPLACE_BE_NL = 11;
    public const MARKETPLACE_ES = 13;

    public const LISTING_DURATION_GTC = 100;
    public const MAX_LENGTH_FOR_OPTION_VALUE = 50;
    public const VARIATION_SKU_MAX_LENGTH = 80;
    public const ITEM_SKU_MAX_LENGTH = 50;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    private $ebayFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Configuration */
    private $componentEbayConfiguration;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $moduleTranslation;
    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $cachePermanent;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /**
     * @param \Ess\M2ePro\Helper\Component\Ebay\Configuration $componentEbayConfiguration
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory
     * @param \Ess\M2ePro\Helper\Module\Translation $moduleTranslation
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Ess\M2ePro\Helper\Data\Cache\Permanent $cachePermanent
     * @param \Ess\M2ePro\Model\Config\Manager $config
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     */
    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Configuration $componentEbayConfiguration,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Module\Translation $moduleTranslation,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $cachePermanent,
        \Ess\M2ePro\Model\Config\Manager $config,
        \Ess\M2ePro\Helper\Data $dataHelper
    ) {
        $this->ebayFactory = $ebayFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->componentEbayConfiguration = $componentEbayConfiguration;
        $this->moduleTranslation = $moduleTranslation;
        $this->config = $config;
        $this->cachePermanent = $cachePermanent;
        $this->dataHelper = $dataHelper;
    }

    // ----------------------------------------

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->moduleTranslation->__('eBay');
    }

    /**
     * @return string
     */
    public function getChannelTitle(): string
    {
        return $this->moduleTranslation->__('eBay');
    }

    // ----------------------------------------

    /**
     * @param string $status
     *
     * @return string|null
     */
    public function getHumanTitleByListingProductStatus($status): ?string
    {
        $statuses = [
            ListingProduct::STATUS_NOT_LISTED => $this->moduleTranslation->__('Not Listed'),
            ListingProduct::STATUS_LISTED     => $this->moduleTranslation->__('Listed'),
            ListingProduct::STATUS_HIDDEN     => $this->moduleTranslation->__('Listed (Hidden)'),
            ListingProduct::STATUS_SOLD       => $this->moduleTranslation->__('Sold'),
            ListingProduct::STATUS_STOPPED    => $this->moduleTranslation->__('Stopped'),
            ListingProduct::STATUS_FINISHED   => $this->moduleTranslation->__('Finished'),
            ListingProduct::STATUS_BLOCKED    => $this->moduleTranslation->__('Pending'),
        ];

        return $statuses[$status] ?? null;
    }

    // ----------------------------------------

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->config->getGroupValue('/component/' . self::NICK . '/', 'mode');
    }

    // ----------------------------------------

    /**
     * @param string $ebayItemId
     * @param string $accountMode
     * @param int $marketplaceId
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getItemUrl(
        $ebayItemId,
        $accountMode = \Ess\M2ePro\Model\Ebay\Account::MODE_PRODUCTION,
        $marketplaceId = null
    ): string {
        $marketplaceId = (int)$marketplaceId;
        if ($marketplaceId <= 0 || $marketplaceId === self::MARKETPLACE_MOTORS) {
            $marketplaceId = self::MARKETPLACE_US;
        }

        /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
        $marketplace = $this->activeRecordFactory->getCachedObjectLoaded('Marketplace', $marketplaceId);

        return $accountMode === \Ess\M2ePro\Model\Ebay\Account::MODE_SANDBOX
            ? $this->getSandboxItemUrl($ebayItemId, $marketplace)
            : 'http://www.' . $marketplace->getUrl() . '/itm/' . (double)$ebayItemId;
    }

    /**
     * @param string $ebayItemId
     * @param \Ess\M2ePro\Model\Marketplace $marketplace
     *
     * @return string
     */
    private function getSandboxItemUrl($ebayItemId, \Ess\M2ePro\Model\Marketplace $marketplace): string
    {
        $domainParts = explode('.', $marketplace->getUrl());

        switch ($marketplace->getId()) {
            case self::MARKETPLACE_US:
                $subDomain = '';
                break;
            case self::MARKETPLACE_BE_FR:
            case self::MARKETPLACE_BE_NL:
                $subDomain = reset($domainParts) . '.';
                break;
            default:
                $subDomain = end($domainParts) . '.';
        }

        return 'https://www.' . $subDomain . 'sandbox.ebay.com/itm/' . (double)$ebayItemId;
    }

    /**
     * @param string $ebayMemberId
     * @param string $accountMode
     *
     * @return string
     */
    public function getMemberUrl($ebayMemberId, $accountMode = \Ess\M2ePro\Model\Ebay\Account::MODE_PRODUCTION): string
    {
        $domain = 'ebay.com';
        if ($accountMode === \Ess\M2ePro\Model\Ebay\Account::MODE_SANDBOX) {
            $domain = 'sandbox.' . $domain;
        }

        return 'http://myworld.' . $domain . '/' . $ebayMemberId;
    }

    // ----------------------------------------

    /**
     * @return bool
     */
    public function isShowTaxCategory(): bool
    {
        return (bool)$this->componentEbayConfiguration->getViewTemplateSellingFormatShowTaxCategory();
    }

    /**
     * @return array
     */
    public function getAvailableDurations(): array
    {
        return [
            '1'                        => $this->moduleTranslation->__('1 day'),
            '3'                        => $this->moduleTranslation->__('3 days'),
            '5'                        => $this->moduleTranslation->__('5 days'),
            '7'                        => $this->moduleTranslation->__('7 days'),
            '10'                       => $this->moduleTranslation->__('10 days'),
            '30'                       => $this->moduleTranslation->__('30 days'),
            self::LISTING_DURATION_GTC => $this->moduleTranslation->__('Good Till Cancelled'),
        ];
    }

    /**
     * @param string $ebayItem
     * @param int $accountId
     *
     * @return \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getListingProductByEbayItem($ebayItem, $accountId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->ebayFactory->getObject('Listing\Product')->getCollection();

        $ebayItem = $collection->getConnection()->quoteInto('?', $ebayItem);
        $accountId = $collection->getConnection()->quoteInto('?', $accountId);

        $collection->getSelect()->join(
            ['mei' => $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable()],
            "(second_table.ebay_item_id = mei.id AND mei.item_id = {$ebayItem}
                                                 AND mei.account_id = {$accountId})",
            []
        );

        if ($collection->getSize() === 0) {
            return null;
        }

        return $collection->getFirstItem();
    }

    // ----------------------------------------

    /**
     * @return string[]
     */
    public function getCurrencies(): array
    {
        return [
            'AUD' => 'Australian Dollar',
            'GBP' => 'British Pound',
            'CAD' => 'Canadian Dollar',
            'CNY' => 'Chinese Renminbi',
            'EUR' => 'Euro',
            'HKD' => 'Hong Kong Dollar',
            'INR' => 'Indian Rupees',
            'MYR' => 'Malaysian Ringgit',
            'PHP' => 'Philippines Peso',
            'PLN' => 'Polish Zloty',
            'SGD' => 'Singapore Dollar',
            'SEK' => 'Sweden Krona',
            'CHF' => 'Swiss Franc',
            'TWD' => 'Taiwanese Dollar',
            'USD' => 'US Dollar',
        ];
    }

    /**
     * @return string[]
     */
    public function getCarriers(): array
    {
        return [
            'dhl'   => 'DHL',
            'fedex' => 'FedEx',
            'ups'   => 'UPS',
            'usps'  => 'USPS',
        ];
    }

    /**
     * @param string $carrierCode
     * @param string|null $title
     *
     * @return string
     */
    public function getCarrierTitle($carrierCode, $title = null): string
    {
        $carriers = $this->getCarriers();
        $carrierCode = strtolower($carrierCode);

        if (isset($carriers[$carrierCode])) {
            return $carriers[$carrierCode];
        }

        if ($title === '' || filter_var($title, FILTER_VALIDATE_URL) !== false) {
            return 'Other';
        }

        return $title;
    }

    // ----------------------------------------

    /**
     * @param array $options
     *
     * @return array
     */
    public function prepareOptionsForVariations(array $options): array
    {
        $set = [];
        foreach ($options['set'] as $optionTitle => $optionsSet) {
            foreach ($optionsSet as $singleOptionKey => $singleOption) {
                $set[trim($optionTitle)][$singleOptionKey] = trim(
                    $this->dataHelper->reduceWordsInString(
                        $singleOption,
                        self::MAX_LENGTH_FOR_OPTION_VALUE
                    )
                );
            }
        }
        $options['set'] = $set;

        foreach ($options['variations'] as &$variation) {
            foreach ($variation as &$singleOption) {
                $singleOption['option'] = trim(
                    $this->dataHelper->reduceWordsInString(
                        $singleOption['option'],
                        self::MAX_LENGTH_FOR_OPTION_VALUE
                    )
                );
                $singleOption['attribute'] = trim($singleOption['attribute']);
            }
        }
        unset($singleOption, $variation);

        return $options;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function prepareOptionsForOrders(array $options): array
    {
        foreach ($options as &$singleOption) {
            if ($singleOption instanceof \Magento\Catalog\Model\Product) {
                $reducedName = trim(
                    $this->dataHelper->reduceWordsInString(
                        $singleOption->getName(),
                        self::MAX_LENGTH_FOR_OPTION_VALUE
                    )
                );
                $singleOption->setData('name', $reducedName);

                continue;
            }

            foreach ($singleOption['values'] as &$singleOptionValue) {
                foreach ($singleOptionValue['labels'] as &$singleOptionLabel) {
                    $singleOptionLabel = trim(
                        $this->dataHelper->reduceWordsInString(
                            $singleOptionLabel,
                            self::MAX_LENGTH_FOR_OPTION_VALUE
                        )
                    );
                }
            }
        }

        if (isset($options['additional']['attributes'])) {
            foreach ($options['additional']['attributes'] as $code => &$title) {
                $title = trim($title);
            }
            unset($title);
        }

        return $options;
    }

    // ----------------------------------------

    /**
     * @return void
     */
    public function clearCache(): void
    {
        $this->cachePermanent->removeTagValues(self::NICK);
    }

    // ----------------------------------------

    /**
     * @param \DateTime|int|string $time
     *
     * @return string
     */
    public function timeToString($time): string
    {
        return $this->getEbayDateTimeObject($time)->format('Y-m-d H:i:s');
    }

    /**
     * @param \DateTime|int|string $time
     *
     * @return int
     */
    public function timeToTimeStamp($time): int
    {
        return (int)$this->getEbayDateTimeObject($time)->format('U');
    }

    /**
     * @param \DateTime|int|string $time
     *
     * @return \DateTime
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function getEbayDateTimeObject($time): \DateTime
    {
        $dateTime = null;

        if ($time instanceof \DateTime) {
            $dateTime = clone $time;
            $dateTime->setTimezone(new \DateTimeZone('UTC'));
        } else {
            is_int($time) && $time = '@' . $time;
            $dateTime = new \DateTime($time, new \DateTimeZone('UTC'));
        }

        if ($dateTime === null) {
            throw new \Ess\M2ePro\Model\Exception('eBay DateTime object is null');
        }

        return $dateTime;
    }
}
