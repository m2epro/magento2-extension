<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Template;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Description
 */
class Description extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_walmart_template_description', 'template_description_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################
}
