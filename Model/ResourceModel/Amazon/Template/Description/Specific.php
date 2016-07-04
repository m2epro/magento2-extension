<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Template\Description;

class Specific extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractDb
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_template_description_specific', 'id');
    }

    //########################################
}