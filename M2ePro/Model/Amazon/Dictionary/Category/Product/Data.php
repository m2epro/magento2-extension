<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Dictionary\Category\Product;

/**
 * Class \Ess\M2ePro\Model\Amazon\Dictionary\Category\Product\Data
 */
class Data extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Category\Product\Data');
    }

    //########################################
}
