<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Listing;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product
 */
class Product extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    protected $walmartFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        $this->walmartFactory = $walmartFactory;

        parent::__construct($helperFactory, $activeRecordFactory, $parentFactory, $context, $connectionName);
    }

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_walmart_listing_product', 'listing_product_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function getChangedItems(
        array $attributes,
        $withStoreFilter = false
    ) {
        return $this->walmartFactory->getObject('Listing\Product')->getResource()->getChangedItems(
            $attributes,
            \Ess\M2ePro\Helper\Component\Walmart::NICK,
            $withStoreFilter
        );
    }

    public function getChangedItemsByListingProduct(
        array $attributes,
        $withStoreFilter = false
    ) {
        return $this->walmartFactory->getObject('Listing\Product')->getResource()->getChangedItemsByListingProduct(
            $attributes,
            \Ess\M2ePro\Helper\Component\Walmart::NICK,
            $withStoreFilter
        );
    }

    public function getChangedItemsByVariationOption(
        array $attributes,
        $withStoreFilter = false
    ) {
        return $this->walmartFactory->getObject('Listing\Product')->getResource()->getChangedItemsByVariationOption(
            $attributes,
            \Ess\M2ePro\Helper\Component\Walmart::NICK,
            $withStoreFilter
        );
    }

    //########################################
}
