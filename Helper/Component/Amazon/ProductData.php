<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Amazon;

class ProductData
{
    private const RECENT_MAX_COUNT = 5;

    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registry;

    /**
     * @param \Ess\M2ePro\Model\Registry\Manager $registry
     */
    public function __construct(
        \Ess\M2ePro\Model\Registry\Manager $registry
    ) {
        $this->registry = $registry;
    }

    // ----------------------------------------

    public function getRecent($marketplaceId, $excludedProductDataNick = null)
    {
        $allRecent = $this->registry->getValueFromJson($this->getConfigGroup());

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
        $allRecent = $this->registry->getValueFromJson($this->getConfigGroup());

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

        $this->registry->setValue($this->getConfigGroup(), $allRecent);
    }

    // ----------------------------------------

    /**
     * @return string
     */
    private function getConfigGroup(): string
    {
        return '/amazon/product_data/recent/';
    }
}
