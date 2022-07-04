<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component;

use Ess\M2ePro\Model\Listing\Product as ListingProduct;

class Walmart
{
    public const NICK = 'walmart';

    public const MARKETPLACE_SYNCHRONIZATION_LOCK_ITEM_NICK = 'walmart_marketplace_synchronization';

    public const MARKETPLACE_US = 37;
    public const MARKETPLACE_CA = 38;

    public const MAX_ALLOWED_FEED_REQUESTS_PER_HOUR = 30;

    public const SKU_MAX_LENGTH = 50;

    public const PRODUCT_PUBLISH_STATUS_PUBLISHED = 'PUBLISHED';
    public const PRODUCT_PUBLISH_STATUS_UNPUBLISHED = 'UNPUBLISHED';
    public const PRODUCT_PUBLISH_STATUS_STAGE = 'STAGE';
    public const PRODUCT_PUBLISH_STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const PRODUCT_PUBLISH_STATUS_READY_TO_PUBLISH = 'READY_TO_PUBLISH';
    public const PRODUCT_PUBLISH_STATUS_SYSTEM_PROBLEM = 'SYSTEM_PROBLEM';

    public const PRODUCT_LIFECYCLE_STATUS_ACTIVE = 'ACTIVE';
    public const PRODUCT_LIFECYCLE_STATUS_RETIRED = 'RETIRED';
    public const PRODUCT_LIFECYCLE_STATUS_ARCHIVED = 'ARCHIVED';

    public const PRODUCT_STATUS_CHANGE_REASON_INVALID_PRICE = 'Reasonable Price Not Satisfied';

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory */
    private $walmartFactory;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $moduleTranslation;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $permanentCache;
    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    /**
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory
     * @param \Ess\M2ePro\Model\Config\Manager $config
     * @param \Ess\M2ePro\Helper\Module\Translation $moduleTranslation
     * @param \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCache
     */
    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\Config\Manager $config,
        \Ess\M2ePro\Helper\Module\Translation $moduleTranslation,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCache
    ) {
        $this->walmartFactory = $walmartFactory;
        $this->moduleTranslation = $moduleTranslation;
        $this->permanentCache = $permanentCache;
        $this->config = $config;
    }

    // ----------------------------------------

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->moduleTranslation->__('Walmart');
    }

    /**
     * @return string
     */
    public function getChannelTitle(): string
    {
        return $this->moduleTranslation->__('Walmart');
    }

    // ----------------------------------------

    /**
     * @param int $status
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
    public function getRegisterUrl($marketplaceId = self::MARKETPLACE_US): string
    {
        $domain = $this->walmartFactory
            ->getCachedObjectLoaded('Marketplace', $marketplaceId)
            ->getUrl();

        if ($marketplaceId === self::MARKETPLACE_CA) {
            return 'https://seller.' . $domain . '/#/generateKey';
        }

        return 'https://developer.' . $domain . '/#/generateKey';
    }

    /**
     * @param int $marketplaceId
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getIdentifierForItemUrl($marketplaceId): string
    {
        switch ($marketplaceId) {
            case \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_US:
                return 'item_id';
            case \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA:
                return 'wpid';
            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown Marketplace ID.');
        }
    }

    /**
     * @param int $productItemId
     * @param int|null $marketplaceId
     *
     * @return string
     */
    public function getItemUrl($productItemId, $marketplaceId = null): string
    {
        $marketplaceId = (int)$marketplaceId;
        $marketplaceId <= 0 && $marketplaceId = self::MARKETPLACE_US;

        $domain = $this->walmartFactory
            ->getCachedObjectLoaded('Marketplace', $marketplaceId)
            ->getUrl();

        return 'https://www.' . $domain . '/ip/' . $productItemId;
    }

    /**
     * todo is not correct. there are no orders to check
     *
     * @param int $orderId
     * @param int $marketplaceId
     *
     * @return string
     */
    public function getOrderUrl($orderId, $marketplaceId = null): string
    {
        $marketplaceId = (int)$marketplaceId;
        $marketplaceId <= 0 && $marketplaceId = self::MARKETPLACE_US;

        $domain = $this->walmartFactory
            ->getCachedObjectLoaded('Marketplace', $marketplaceId)
            ->getUrl();

        return 'https://seller.' . $domain . '/order-management/details./' . $orderId;
    }

    // ----------------------------------------

    /**
     * @return string
     */
    public function getApplicationName()
    {
        return $this->config->getGroupValue('/walmart/', 'application_name');
    }

    // ----------------------------------------

    /**
     * @return string[]
     */
    public function getCarriers(): array
    {
        return [
            'ups'      => 'UPS',
            'usps'     => 'USPS',
            'fedex'    => 'FedEx',
            'airborne' => 'Airborne',
            'ontrac'   => 'OnTrac',
            'dhl'      => 'DHL',
            'ng'       => 'NG',
            'ls'       => 'LS',
            'uds'      => 'UDS',
            'upsmi'    => 'UPSMI',
            'fdx'      => 'FDX',
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

        return $carriers[$carrierCode] ?? $title;
    }

    // ----------------------------------------

    /**
     * @return \Magento\Framework\Data\Collection\AbstractDb|\Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getMarketplacesAvailableForApiCreation()
    {
        return $this->walmartFactory->getObject('Marketplace')->getCollection()
                                    ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
                                    ->setOrder('sorder', 'ASC');
    }

    // ----------------------------------------

    /**
     * @param string $publishStatus
     * @param string $lifecycleStatus
     * @param int $onlineQty
     *
     * @return int
     */
    public function getResultProductStatus($publishStatus, $lifecycleStatus, $onlineQty)
    {
        if (
            !in_array(
                $publishStatus,
                [
                    self::PRODUCT_PUBLISH_STATUS_PUBLISHED,
                    self::PRODUCT_PUBLISH_STATUS_STAGE,
                ]
            )
            || $lifecycleStatus !== self::PRODUCT_LIFECYCLE_STATUS_ACTIVE
        ) {
            return ListingProduct::STATUS_BLOCKED;
        }

        return $onlineQty > 0
            ? ListingProduct::STATUS_LISTED
            : ListingProduct::STATUS_STOPPED;
    }

    // ----------------------------------------

    /**
     * @return void
     */
    public function clearCache(): void
    {
        $this->permanentCache->removeTagValues(self::NICK);
    }
}
