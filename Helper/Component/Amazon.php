<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component;

use \Ess\M2ePro\Model\Listing\Product as ListingProduct;

/**
 * Class \Ess\M2ePro\Helper\Component\Amazon
 */
class Amazon extends \Ess\M2ePro\Helper\AbstractHelper
{
    const NICK  = 'amazon';

    const MARKETPLACE_SYNCHRONIZATION_LOCK_ITEM_NICK = 'amazon_marketplace_synchronization';

    const MARKETPLACE_CA = 24;
    const MARKETPLACE_DE = 25;
    const MARKETPLACE_FR = 26;
    const MARKETPLACE_UK = 28;
    const MARKETPLACE_US = 29;
    const MARKETPLACE_ES = 30;
    const MARKETPLACE_IT = 31;
    const MARKETPLACE_CN = 32;
    const MARKETPLACE_MX = 34;
    const MARKETPLACE_AU = 35;
    const MARKETPLACE_NL = 39;
    const MARKETPLACE_TR = 40;
    const MARKETPLACE_SE = 41;
    const MARKETPLACE_JP = 42;
    const MARKETPLACE_PL = 43;

    protected $regionCollection;
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Magento\Directory\Model\ResourceModel\Region\Collection $regionCollection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->regionCollection = $regionCollection;
        $this->amazonFactory = $amazonFactory;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getTitle()
    {
        return $this->getHelper('Module\Translation')->__('Amazon');
    }

    public function getChannelTitle()
    {
        return $this->getHelper('Module\Translation')->__('Amazon');
    }

    //########################################

    public function getHumanTitleByListingProductStatus($status)
    {
        $statuses = [
            ListingProduct::STATUS_UNKNOWN    => $this->getHelper('Module\Translation')->__('Unknown'),
            ListingProduct::STATUS_NOT_LISTED => $this->getHelper('Module\Translation')->__('Not Listed'),
            ListingProduct::STATUS_LISTED     => $this->getHelper('Module\Translation')->__('Active'),
            ListingProduct::STATUS_STOPPED    => $this->getHelper('Module\Translation')->__('Inactive'),
            ListingProduct::STATUS_BLOCKED    => $this->getHelper('Module\Translation')->__('Inactive (Blocked)')
        ];

        if (!isset($statuses[$status])) {
            return null;
        }

        return $statuses[$status];
    }

    //########################################

    public function isEnabled()
    {
        return (bool)$this->getHelper('Module')->getConfig()->getGroupValue('/component/'.self::NICK.'/', 'mode');
    }

    //########################################

    public function getRegisterUrl($marketplaceId = null)
    {
        $marketplaceId = (int)$marketplaceId;
        $marketplaceId <= 0 && $marketplaceId = self::MARKETPLACE_US;

        $domain = $this->amazonFactory->getCachedObjectLoaded('Marketplace', $marketplaceId)->getUrl();
        $applicationName = $this->getApplicationName();

        $marketplace = $this->amazonFactory->getCachedObjectLoaded('Marketplace', $marketplaceId);

        return 'https://sellercentral.'.
                $domain.
                '/gp/mws/registration/register.html?ie=UTF8&*Version*=1&*entries*=0&applicationName='.
                rawurlencode($applicationName).'&appDevMWSAccountId='.
                $marketplace->getChildObject()->getDeveloperKey();
    }

    public function getItemUrl($productId, $marketplaceId = null)
    {
        $marketplaceId = (int)$marketplaceId;
        $marketplaceId <= 0 && $marketplaceId = self::MARKETPLACE_US;

        $domain = $this->amazonFactory->getCachedObjectLoaded('Marketplace', $marketplaceId)->getUrl();

        return 'http://'.$domain.'/gp/product/'.$productId;
    }

    public function getOrderUrl($orderId, $marketplaceId = null)
    {
        $marketplaceId = (int)$marketplaceId;
        $marketplaceId <= 0 && $marketplaceId = self::MARKETPLACE_US;

        $domain = $this->amazonFactory->getCachedObjectLoaded('Marketplace', $marketplaceId)->getUrl();

        return 'https://sellercentral.'.$domain.'/orders-v3/order/'.$orderId;
    }

    //########################################

    public function isASIN($string)
    {
        if (strlen($string) != 10) {
            return false;
        }

        if (!preg_match('/^B[A-Z0-9]{9}$/', $string)) {
            return false;
        }

        return true;
    }

    public function getApplicationName()
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue('/amazon/', 'application_name');
    }

    // ----------------------------------------

    public function getCurrencies()
    {
        return  [
            'GBP' => 'British Pound',
            'EUR' => 'Euro',
            'USD' => 'US Dollar',
        ];
    }

    public function getCarriers()
    {
        return [
            'usps'  => 'USPS',
            'ups'   => 'UPS',
            'fedex' => 'FedEx',
            'dhl'   => 'DHL'
        ];
    }

    public function getCarrierTitle($carrierCode, $title)
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
        $collection = $this->getMarketplacesAvailableForApiCreation();
        return $collection->addFieldToFilter('is_new_asin_available', 1);
    }

    //########################################

    public function getStatesList()
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
                    'Palau'
                ]
            ]
        );

        $states = [];

        foreach ($collection->getItems() as $state) {
            $states[$state->getCode()] = $state->getName();
        }

        return $states;
    }

    //########################################

    public function clearCache()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagsValues(self::NICK);
    }

    //########################################
}
