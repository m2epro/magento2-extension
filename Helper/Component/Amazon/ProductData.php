<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Amazon;

/**
 * Class \Ess\M2ePro\Helper\Component\Amazon\ProductData
 */
class ProductData extends \Ess\M2ePro\Helper\AbstractHelper
{
    const RECENT_MAX_COUNT = 5;

    protected $activeRecordFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getRecent($marketplaceId, $excludedProductDataNick = null)
    {
        $allRecent = $this->getHelper('Module')->getRegistry()->getValueFromJson($this->getConfigGroup());

        if (!isset($allRecent[$marketplaceId])) {
            return [];
        }

        $recent = $allRecent[$marketplaceId];

        foreach ($recent as $index => $recentProductDataNick) {
            if ($excludedProductDataNick == $recentProductDataNick) {
                unset($recent[$index]);
            }
        }

        return array_reverse($recent);
    }

    public function addRecent($marketplaceId, $productDataNick)
    {
        $allRecent = $this->getHelper('Module')->getRegistry()->getValueFromJson($this->getConfigGroup());

        !isset($allRecent[$marketplaceId]) && $allRecent[$marketplaceId] = [];

        $recent = $allRecent[$marketplaceId];
        foreach ($recent as $recentProductDataNick) {
            if ($productDataNick == $recentProductDataNick) {
                return;
            }
        }

        if (count($recent) >= self::RECENT_MAX_COUNT) {
            array_shift($recent);
        }

        $recent[] = $productDataNick;
        $allRecent[$marketplaceId] = $recent;

        $this->getHelper('Module')->getRegistry()->setValue($this->getConfigGroup(), $allRecent);
    }

    //########################################

    private function getConfigGroup()
    {
        return "/amazon/product_data/recent/";
    }

    //########################################
}
