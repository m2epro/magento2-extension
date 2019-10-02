<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product;

/**
 * Class Variation
 * @package Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product
 */
class Variation extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_listing_product_variation', 'listing_product_variation_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################
}
