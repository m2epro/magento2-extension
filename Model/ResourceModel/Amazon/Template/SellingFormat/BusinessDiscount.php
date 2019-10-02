<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Template\SellingFormat;

/**
 * Class BusinessDiscount
 * @package Ess\M2ePro\Model\ResourceModel\Amazon\Template\SellingFormat
 */
class BusinessDiscount extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_template_selling_format_business_discount', 'id');
    }

    //########################################
}
