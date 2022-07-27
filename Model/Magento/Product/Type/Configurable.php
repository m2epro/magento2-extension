<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * Due to strange changes in addStoreFilter method since Magento version 1.9.x,
 * we were forced to setStore for collection manually
 */

namespace Ess\M2ePro\Model\Magento\Product\Type;

/**
 * Class \Ess\M2ePro\Model\Magento\Product\Type\Configurable
 */
class Configurable extends \Magento\ConfigurableProduct\Model\Product\Type\Configurable
{
    //########################################

    /**
     * {@inheritdoc}
     */
    public function getUsedProductCollection($product = null)
    {
        $collection = parent::getUsedProductCollection($product);

        if ($this->getStoreFilter($product) !== null) {
            $collection->setStoreId($this->getStoreFilter($product));
        }

        return $collection;
    }

    public function cleanProductCache($product)
    {
        $cache = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\App\Cache\Type\Collection::class);
        $cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_TAG, [self::TYPE_CODE . '_' . $product->getId()]);
    }

    //########################################
}
