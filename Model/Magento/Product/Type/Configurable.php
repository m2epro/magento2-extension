<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

/**
 * Due to strange changes in addStoreFilter method since Magento version 1.9.x,
 * we were forced to setStore for collection manually
 */

namespace Ess\M2ePro\Model\Magento\Product\Type;

class Configurable extends \Magento\ConfigurableProduct\Model\Product\Type\Configurable
{
    //########################################

    /**
     * {@inheritdoc}
     */
    public function getUsedProductCollection($product = null)
    {
        $collection = parent::getUsedProductCollection($product);

        if (!is_null($this->getStoreFilter($product))) {
            $collection->setStoreId($this->getStoreFilter($product));
        }

        return $collection;
    }

    //########################################
}
