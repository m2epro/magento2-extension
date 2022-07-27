<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component;

use Ess\M2ePro\Model\Listing\Product as ListingProduct;

class Amazon
{
    public const NICK = 'amazon';

    public const MARKETPLACE_SYNCHRONIZATION_LOCK_ITEM_NICK = 'amazon_marketplace_synchronization';

    public const MARKETPLACE_CA = 24;
    public const MARKETPLACE_DE = 25;
    public const MARKETPLACE_FR = 26;
    public const MARKETPLACE_UK = 28;
    public const MARKETPLACE_US = 29;
    public const MARKETPLACE_ES = 30;
    public const MARKETPLACE_IT = 31;
    public const MARKETPLACE_CN = 32;
    public const MARKETPLACE_MX = 34;
    public const MARKETPLACE_AU = 35;
    public const MARKETPLACE_NL = 39;
    public const MARKETPLACE_TR = 40;
    public const MARKETPLACE_SE = 41;
    public const MARKETPLACE_JP = 42;
    public const MARKETPLACE_PL = 43;

    public const EEA_COUNTRY_CODES = [
        'AT', 'BE', 'BG', 'HR', 'CY',
        'CZ', 'DK', 'EE', 'FI', 'FR',
        'DE', 'GR', 'HU', 'IS', 'IE',
        'IT', 'LV', 'LI', 'LT', 'LU',
        'MT', 'NL', 'NO', 'PL', 'PT',
        'RO', 'SK', 'SI', 'ES', 'SE',
    ];

    /** @var \Magento\Directory\Model\ResourceModel\Country\CollectionFactory */
    private $countryCollectionFactory;
    /** @var \Magento\Directory\Model\ResourceModel\Region\Collection */
    private $regionCollection;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    private $amazonFactory;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $moduleTranslation;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $cachePermanent;
    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    /**
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Region\Collection $regionCollection
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Helper\Module\Translation $moduleTranslation
     * @param \Ess\M2ePro\Helper\Data\Cache\Permanent $cachePermanent
     * @param \Ess\M2ePro\Model\Config\Manager $config
     */
    public function __construct(
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Region\Collection $regionCollection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Module\Translation $moduleTranslation,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $cachePermanent,
        \Ess\M2ePro\Model\Config\Manager $config
    ) {
        $this->countryCollectionFactory = $countryCollectionFactory;
        $this->regionCollection = $regionCollection;
        $this->amazonFactory = $amazonFactory;
        $this->moduleTranslation = $moduleTranslation;
        $this->cachePermanent = $cachePermanent;
        $this->config = $config;
    }

    // ----------------------------------------

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->moduleTranslation->__('Amazon');
    }

    /**
     * @return string
     */
    public function getChannelTitle(): string
    {
        return $this->moduleTranslation->__('Amazon');
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
            ListingProduct::STATUS_UNKNOWN    => $this->moduleTranslation->__('Unknown'),
            ListingProduct::STATUS_NOT_LISTED => $this->moduleTranslation->__('Not Listed'),
            ListingProduct::STATUS_LISTED     => $this->moduleTranslation->__('Active'),
            ListingProduct::STATUS_STOPPED    => $this->moduleTranslation->__('Inactive'),
            ListingProduct::STATUS_BLOCKED    => $this->moduleTranslation->__('Incomplete'),
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
     * @param int $marketplaceId
     *
     * @return string
     */
    public function getRegisterUrl($marketplaceId = null): string
    {
        $marketplaceId = (int)$marketplaceId;
        $marketplaceId <= 0 && $marketplaceId = self::MARKETPLACE_US;

        $domain = $this->amazonFactory->getCachedObjectLoaded('Marketplace', $marketplaceId)->getUrl();
        $applicationName = $this->getApplicationName();

        $marketplace = $this->amazonFactory->getCachedObjectLoaded('Marketplace', $marketplaceId);

        return 'https://sellercentral.' .
            $domain .
            '/gp/mws/registration/register.html?ie=UTF8&*Version*=1&*entries*=0&applicationName=' .
            rawurlencode($applicationName) . '&appDevMWSAccountId=' .
            $marketplace->getChildObject()->getDeveloperKey();
    }

    /**
     * @param int $productId
     * @param int|null $marketplaceId
     *
     * @return string
     */
    public function getItemUrl($productId, $marketplaceId = null): string
    {
        $marketplaceId = (int)$marketplaceId;
        $marketplaceId <= 0 && $marketplaceId = self::MARKETPLACE_US;

        $domain = $this->amazonFactory->getCachedObjectLoaded('Marketplace', $marketplaceId)->getUrl();

        return 'http://' . $domain . '/gp/product/' . $productId;
    }

    /**
     * @param int $orderId
     * @param int|null $marketplaceId
     *
     * @return string
     */
    public function getOrderUrl($orderId, $marketplaceId = null): string
    {
        $marketplaceId = (int)$marketplaceId;
        $marketplaceId <= 0 && $marketplaceId = self::MARKETPLACE_US;

        $domain = $this->amazonFactory->getCachedObjectLoaded('Marketplace', $marketplaceId)->getUrl();

        return 'https://sellercentral.' . $domain . '/orders-v3/order/' . $orderId;
    }

    // ----------------------------------------

    /**
     * @param string $string
     *
     * @return bool
     */
    public function isASIN($string): bool
    {
        $string = (string)$string;
        if (strlen($string) !== 10) {
            return false;
        }

        if (!preg_match('/^B[A-Z\d]{9}$/', $string)) {
            return false;
        }

        return true;
    }

    public function getApplicationName()
    {
        return $this->config->getGroupValue('/amazon/', 'application_name');
    }

    // ----------------------------------------

    /**
     * @return string[]
     */
    public function getCurrencies(): array
    {
        return [
            'GBP' => 'British Pound',
            'EUR' => 'Euro',
            'USD' => 'US Dollar',
        ];
    }

    /**
     * @return string[]
     */
    public function getCarriers(): array
    {
        return [
            'usps'  => 'USPS',
            'ups'   => 'UPS',
            'fedex' => 'FedEx',
            'dhl'   => 'DHL',
        ];
    }

    /**
     * @param string $carrierCode
     * @param string $title
     *
     * @return string
     */
    public function getCarrierTitle($carrierCode, $title): string
    {
        $carriers = $this->getCarriers();
        $carrierCode = strtolower($carrierCode);

        if (isset($carriers[$carrierCode])) {
            return $carriers[$carrierCode];
        }

        return $title;
    }

    // ----------------------------------------

    public function getMarketplacesAvailableForApiCreation()
    {
        return $this->amazonFactory->getObject('Marketplace')->getCollection()
                                   ->addFieldToFilter('component_mode', self::NICK)
                                   ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
                                   ->addFieldToFilter('developer_key', ['notnull' => true])
                                   ->setOrder('sorder', 'ASC');
    }

    public function getMarketplacesAvailableForAsinCreation()
    {
        return $this->getMarketplacesAvailableForApiCreation()->addFieldToFilter('is_new_asin_available', 1);
    }

    // ----------------------------------------

    /**
     * @return array
     */
    public function getStatesList(): array
    {
        $collection = $this->regionCollection->addCountryFilter('US');
        $collection->addFieldToFilter(
            'default_name',
            [
                'nin' => [
                    'Armed Forces Africa',
                    'Armed Forces Americas',
                    'Armed Forces Canada',
                    'Armed Forces Europe',
                    'Armed Forces Middle East',
                    'Armed Forces Pacific',
                    'Federated States Of Micronesia',
                    'Marshall Islands',
                    'Palau',
                ],
            ]
        );

        $states = [];

        foreach ($collection->getItems() as $state) {
            $states[$state->getCode()] = $state->getName();
        }

        return $states;
    }

    /**
     * @return array
     */
    public function getEEACountriesList(): array
    {
        $collection = $this->countryCollectionFactory
            ->create()
            ->addFieldToSelect(['iso2_code'])
            ->addFieldToFilter(
                'iso2_code',
                ['in' => self::EEA_COUNTRY_CODES]
            );

        $tempData = [];
        /** @var \Magento\Directory\Model\Country $item */
        foreach ($collection->getItems() as $item) {
            $tempData[] = [
                'name' => $item->getName(),
                'code' => $item->getData('iso2_code')
            ];
        }

        $compare = function ($a, $b) {
            if ($a['name'] === $b['name']) {
                return 0;
            }

            return ($a['name'] < $b['name']) ? -1 : 1;
        };
        uasort($tempData, $compare);

        $data = [];
        foreach ($tempData as $value) {
            $data[$value['code']] = $value['name'];
        }

        return $data;
    }

    /**
     * @return void
     */
    public function clearCache(): void
    {
        $this->cachePermanent->removeTagValues(self::NICK);
    }
}
