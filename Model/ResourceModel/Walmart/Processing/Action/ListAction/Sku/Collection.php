<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Processing\Action\ListAction\Sku;

/**
 * Class Collection
 * @package Ess\M2ePro\Model\ResourceModel\Walmart\Processing\Action\ListAction\Sku
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    // ########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            'Ess\M2ePro\Model\Walmart\Processing\Action\ListAction\Sku',
            'Ess\M2ePro\Model\ResourceModel\Walmart\Processing\Action\ListAction\Sku'
        );
    }

    // ########################################
}
