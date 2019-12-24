<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Template\Description;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Amazon\Template\Description\Specific
 */
class Specific extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_template_description_specific', 'id');
    }

    //########################################
}
