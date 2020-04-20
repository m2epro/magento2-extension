<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Dictionary\Motor;

/**
 * Class \Ess\M2ePro\Model\Ebay\Dictionary\Motor\Epid
 */
class Epid extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Dictionary\Motor\Epid');
    }

    //########################################
}
