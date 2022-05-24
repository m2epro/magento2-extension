<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Amazon;

class ProductData extends \Ess\M2ePro\Helper\AbstractHelper
{
    const RECENT_MAX_COUNT = 5;

    /** @var \Ess\M2ePro\Helper\Module */
    protected $helperModule;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    public function __construct(
        \Ess\M2ePro\Helper\Module $helperModule,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($helperFactory, $context);

        $this->helperModule = $helperModule;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->resourceConnection = $resourceConnection;
    }

    //########################################

    public function getRecent($marketplaceId, $excludedProductDataNick = null)
    {
        $allRecent = $this->helperModule->getRegistry()->getValueFromJson($this->getConfigGroup());

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
        $allRecent = $this->helperModule->getRegistry()->getValueFromJson($this->getConfigGroup());

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

        $this->helperModule->getRegistry()->setValue($this->getConfigGroup(), $allRecent);
    }

    //########################################

    private function getConfigGroup()
    {
        return "/amazon/product_data/recent/";
    }

    //########################################
}
