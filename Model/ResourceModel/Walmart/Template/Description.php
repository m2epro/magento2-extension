<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Template;

class Description extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    /** @var bool */
    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_TEMPLATE_DESCRIPTION,
            'template_description_id'
        );
        $this->_isPkAutoIncrement = false;
    }

    //########################################
}
