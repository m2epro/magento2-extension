<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component;

use \Ess\M2ePro\Model\Listing\Product as ListingProduct;

/**
 * Class \Ess\M2ePro\Helper\Component\Walmart
 */
class Walmart extends \Ess\M2ePro\Helper\AbstractHelper
{
    const NICK  = 'walmart';

    const MARKETPLACE_US = 37;
    const MARKETPLACE_CA = 38;

    const MAX_ALLOWED_FEED_REQUESTS_PER_HOUR = 30;

    const SKU_MAX_LENGTH = 50;

    const PRODUCT_PUBLISH_STATUS_PUBLISHED        = 'PUBLISHED';
    const PRODUCT_PUBLISH_STATUS_UNPUBLISHED      = 'UNPUBLISHED';
    const PRODUCT_PUBLISH_STATUS_STAGE            = 'STAGE';
    const PRODUCT_PUBLISH_STATUS_IN_PROGRESS      = 'IN_PROGRESS';
    const PRODUCT_PUBLISH_STATUS_READY_TO_PUBLISH = 'READY_TO_PUBLISH';
    const PRODUCT_PUBLISH_STATUS_SYSTEM_PROBLEM   = 'SYSTEM_PROBLEM';

    const PRODUCT_LIFECYCLE_STATUS_ACTIVE   = 'ACTIVE';
    const PRODUCT_LIFECYCLE_STATUS_RETIRED  = 'RETIRED';
    const PRODUCT_LIFECYCLE_STATUS_ARCHIVED = 'ARCHIVED';

    const PRODUCT_STATUS_CHANGE_REASON_INVALID_PRICE = 'Reasonable Price Not Satisfied';

    private $walmartFactory;
    private $activeRecordFactory;
    private $moduleConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->walmartFactory = $walmartFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->moduleConfig = $moduleConfig;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getTitle()
    {
        return $this->helperFactory->getObject('Module\Translation')->__('Walmart');
    }

    public function getChannelTitle()
    {
        return $this->helperFactory->getObject('Module\Translation')->__('Walmart');
    }

    //########################################

    public function getHumanTitleByListingProductStatus($status)
    {
        $statuses = [
            ListingProduct::STATUS_UNKNOWN    =>
                $this->helperFactory->getObject('Module\Translation')->__('Unknown'),
            ListingProduct::STATUS_NOT_LISTED =>
                $this->helperFactory->getObject('Module\Translation')->__('Not Listed'),
            ListingProduct::STATUS_LISTED     =>
                $this->helperFactory->getObject('Module\Translation')->__('Active'),
            ListingProduct::STATUS_STOPPED    =>
                $this->helperFactory->getObject('Module\Translation')->__('Inactive'),
            ListingProduct::STATUS_BLOCKED    =>
                $this->helperFactory->getObject('Module\Translation')->__('Inactive (Blocked)')
        ];

        if (!isset($statuses[$status])) {
            return null;
        }

        return $statuses[$status];
    }

    //########################################

    public function isEnabled()
    {
        return (bool)$this->moduleConfig->getGroupValue('/component/'.self::NICK.'/', 'mode');
    }

    public function isAllowed()
    {
        return (bool)$this->moduleConfig->getGroupValue('/component/'.self::NICK.'/', 'allowed');
    }

    public function isActive()
    {
        return $this->isEnabled() && $this->isAllowed();
    }

    //########################################

    public function getRegisterUrl($marketplaceId = self::MARKETPLACE_US)
    {

        $domain = $this->walmartFactory
            ->getCachedObjectLoaded('Marketplace', $marketplaceId)
            ->getUrl();

        if ($marketplaceId == self::MARKETPLACE_CA) {
            return 'https://seller.' . $domain . '/#/generateKey';
        }

        return 'https://developer.' . $domain . '/#/generateKey';
    }

    public function getItemUrl($productItemId, $marketplaceId = null)
    {
        $marketplaceId = (int)$marketplaceId;
        $marketplaceId <= 0 && $marketplaceId = self::MARKETPLACE_US;

        $domain = $this->walmartFactory
            ->getCachedObjectLoaded('Marketplace', $marketplaceId)
            ->getUrl();

        return 'https://'.$domain.'/ip/'.$productItemId;
    }

    //todo is not correct. there are no orders to check
    public function getOrderUrl($orderId, $marketplaceId = null)
    {
        $marketplaceId = (int)$marketplaceId;
        $marketplaceId <= 0 && $marketplaceId = self::MARKETPLACE_US;

        $domain = $this->walmartFactory
            ->getCachedObjectLoaded('Marketplace', $marketplaceId)
            ->getUrl();

        return 'https://seller.'.$domain.'/order-management/details./'.$orderId;
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
        return (bool)$this->moduleConfig->getGroupValue('/walmart/', 'application_name');
    }

    // ----------------------------------------

    public function getCarriers()
    {
        return [
            'usps'  => 'USPS',
            'ups'   => 'UPS',
            'fedex' => 'FedEx',
            'dhl'   => 'DHL',
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
        return $this->walmartFactory->getObject('Marketplace')->getCollection()
                    ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
                    ->setOrder('sorder', 'ASC');
    }

    //########################################

    public function getResultProductStatus($publishStatus, $lifecycleStatus, $onlineQty)
    {
        if (!in_array($publishStatus, [self::PRODUCT_PUBLISH_STATUS_PUBLISHED,
                                            self::PRODUCT_PUBLISH_STATUS_STAGE]) ||
            $lifecycleStatus != self::PRODUCT_LIFECYCLE_STATUS_ACTIVE
        ) {
            return ListingProduct::STATUS_BLOCKED;
        }

        return $onlineQty > 0
            ? ListingProduct::STATUS_LISTED
            : ListingProduct::STATUS_STOPPED;
    }

    //########################################

    public function clearCache()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagsValues(self::NICK);
    }

    //########################################
}
