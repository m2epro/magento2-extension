<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Template\Category;

/**
 * Class Specific
 * @package Ess\M2ePro\Model\ResourceModel\Walmart\Template\Category
 */
class Specific extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_walmart_template_category_specific', 'id');
    }

    //########################################
}
