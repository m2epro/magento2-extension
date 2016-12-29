<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Amazon;

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
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getRecent($marketplaceId, $excludedProductDataNick = null)
    {
        /** @var $registryModel \Ess\M2ePro\Model\Registry */
        $registryModel = $this->activeRecordFactory->getObjectLoaded('Registry', $this->getConfigGroup(), 'key', false);

        if (is_null($registryModel)) {
            return [];
        }

        $allRecent = $registryModel->getValueFromJson();

        if (!isset($allRecent[$marketplaceId])) {
            return array();
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
        $key = $this->getConfigGroup();
        $allRecent = [];

        /** @var $registryModel \Ess\M2ePro\Model\Registry */
        $registryModel = $this->activeRecordFactory->getObjectLoaded('Registry', $key, 'key', false);
        if (is_null($registryModel)) {
            $registryModel = $this->activeRecordFactory->getObject('Registry');
        } else {
            $allRecent = $registryModel->getValueFromJson();
        }

        !isset($allRecent[$marketplaceId]) && $allRecent[$marketplaceId] = array();

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

        $registryModel->addData(array(
            'key'   => $key,
            'value' => $this->getHelper('Data')->jsonEncode($allRecent)
        ))->save();
    }

    //########################################

    private function getConfigGroup()
    {
        return "/amazon/product_data/recent/";
    }

    //########################################
}