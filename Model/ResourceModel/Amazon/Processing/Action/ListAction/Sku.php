<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Processing\Action\ListAction;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Amazon\Processing\Action\ListAction\Sku
 */
class Sku extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    // ########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_processing_action_list_sku', 'id');
    }

    // ########################################
}
