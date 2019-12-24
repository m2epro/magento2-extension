<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat\Promotion
 */
class Promotion extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_walmart_template_selling_format_promotion', 'id');
    }

    //########################################
}
