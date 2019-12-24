<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Template;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Template\SellingFormat
 */
class SellingFormat extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Parent\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_template_selling_format', 'id');
    }

    //########################################
}
