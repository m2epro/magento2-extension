<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Listing;

class Product extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_listing_product', 'listing_product_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function getChangedItems(array $attributes,
                                    $withStoreFilter = false)
    {
        return $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getChangedItems(
            $attributes,
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            $withStoreFilter
        );
    }

    public function getChangedItemsByListingProduct(array $attributes,
                                                    $withStoreFilter = false)
    {
        return $this->activeRecordFactory->getObject('Listing\Product')
            ->getResource()->getChangedItemsByListingProduct(
                $attributes,
                \Ess\M2ePro\Helper\Component\Amazon::NICK,
                $withStoreFilter
            );
    }

    public function getChangedItemsByVariationOption(array $attributes,
                                                     $withStoreFilter = false)
    {
        return $this->activeRecordFactory->getObject('Listing\Product')
            ->getResource()->getChangedItemsByVariationOption(
                $attributes,
                \Ess\M2ePro\Helper\Component\Amazon::NICK,
                $withStoreFilter
            );
    }

    //########################################
}