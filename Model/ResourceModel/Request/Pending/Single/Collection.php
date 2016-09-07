<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Request\Pending\Single;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    // ########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            'Ess\M2ePro\Model\Request\Pending\Single',
            'Ess\M2ePro\Model\ResourceModel\Request\Pending\Single'
        );
    }

    // ########################################

    public function setOnlyExpiredItemsFilter()
    {
        $this->addFieldToFilter('expiration_date', array('lt' => $this->getHelper('Data')->getCurrentGmtDate()));
        return $this;
    }

    public function setOnlyOutdatedItemsFilter()
    {
        $this->getSelect()->where(new \Zend_Db_Expr('DATE_ADD(`expiration_date`, INTERVAL 12 HOUR) < NOW()'));
        return $this;
    }

    // ########################################
}