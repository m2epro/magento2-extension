<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductTaxCode;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            'Ess\M2ePro\Model\Amazon\Template\ProductTaxCode',
            'Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductTaxCode'
        );
    }

    //########################################
}