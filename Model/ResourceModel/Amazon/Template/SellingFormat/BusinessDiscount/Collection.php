<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Template\SellingFormat\BusinessDiscount;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Amazon\Template\SellingFormat\BusinessDiscount\Collection
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            'Ess\M2ePro\Model\Amazon\Template\SellingFormat\BusinessDiscount',
            'Ess\M2ePro\Model\ResourceModel\Amazon\Template\SellingFormat\BusinessDiscount'
        );
    }

    //########################################
}
