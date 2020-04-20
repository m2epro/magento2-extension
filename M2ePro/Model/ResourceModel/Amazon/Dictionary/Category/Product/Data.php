<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Category\Product;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Category\Product\Data
 */
class Data extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    protected function _construct()
    {
        $this->_init('m2epro_amazon_dictionary_category_product_data', 'id');
    }

    //########################################
}
