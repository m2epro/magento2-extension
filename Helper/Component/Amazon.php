<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component;

use \Ess\M2ePro\Model\Listing\Product as ListingProduct;

class Amazon extends \Ess\M2ePro\Helper\AbstractHelper
{
    const NICK  = 'amazon';

    const MARKETPLACE_CA = 24;
    const MARKETPLACE_DE = 25;
    const MARKETPLACE_US = 29;
    const MARKETPLACE_JP = 27;
    const MARKETPLACE_CN = 32;

    protected $amazonFactory;
    protected $moduleConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->amazonFactory = $amazonFactory;
        $this->moduleConfig = $moduleConfig;
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
        $statuses = array(
            ListingProduct::STATUS_UNKNOWN    => $this->getHelper('Module\Translation')->__('Unknown'),
            ListingProduct::STATUS_NOT_LISTED => $this->getHelper('Module\Translation')->__('Not Listed'),
            ListingProduct::STATUS_LISTED     => $this->getHelper('Module\Translation')->__('Active'),
            ListingProduct::STATUS_STOPPED    => $this->getHelper('Module\Translation')->__('Inactive'),
            ListingProduct::STATUS_BLOCKED    => $this->getHelper('Module\Translation')->__('Inactive (Blocked)')
        );

        if (!isset($statuses[$status])) {
            return NULL;
        }

        return $statuses[$status];
    }

    //########################################

    public function isEnabled()
    {
        return (bool)$this->moduleConfig->getGroupValue('/component/'.self::NICK.'/', 'mode');
    }

    //########################################

    public function getRegisterUrl($marketplaceId = NULL)
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

    public function getItemUrl($productId, $marketplaceId = NULL)
    {
        $marketplaceId = (int)$marketplaceId;
        $marketplaceId <= 0 && $marketplaceId = self::MARKETPLACE_US;

        $domain = $this->amazonFactory->getCachedObjectLoaded('Marketplace',$marketplaceId)->getUrl();

        return 'http://'.$domain.'/gp/product/'.$productId;
    }

    public function getOrderUrl($orderId, $marketplaceId = NULL)
    {
        $marketplaceId = (int)$marketplaceId;
        $marketplaceId <= 0 && $marketplaceId = self::MARKETPLACE_US;

        $domain = $this->amazonFactory->getCachedObjectLoaded('Marketplace',$marketplaceId)->getUrl();

        return 'https://sellercentral.'.$domain.'/gp/orders-v2/details/?orderID='.$orderId;
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
        return $this->moduleConfig->getGroupValue('/amazon/', 'application_name');
    }

    // ----------------------------------------

    public function getCurrencies()
    {
        return array (
            'GBP' => 'British Pound',
            'EUR' => 'Euro',
            'USD' => 'US Dollar',
        );
    }

    public function getCarriers()
    {
        return array(
            'usps'  => 'USPS',
            'ups'   => 'UPS',
            'fedex' => 'FedEx',
            'dhl'   => 'DHL',
            'Fastway',
            'GLS',
            'GO!',
            'Hermes Logistik Gruppe',
            'Royal Mail',
            'Parcelforce',
            'City Link',
            'TNT',
            'Target',
            'SagawaExpress',
            'NipponExpress',
            'YamatoTransport'
        );
    }

    // ----------------------------------------

    public function getMarketplacesAvailableForApiCreation()
    {
        return $this->amazonFactory->getObject('Marketplace')->getCollection()
                    ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
                    ->addFieldToFilter('developer_key', array('notnull' => true))
                    ->setOrder('sorder', 'ASC');
    }

    public function getMarketplacesAvailableForAsinCreation()
    {
        $collection = $this->getMarketplacesAvailableForApiCreation();
        return $collection->addFieldToFilter('is_new_asin_available', 1);
    }

    //########################################

    public function clearCache()
    {
        $this->getHelper('Data\Cache\Permanent')->removeTagsValues(self::NICK);
    }

    //########################################
}