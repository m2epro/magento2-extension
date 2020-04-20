<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

/**
 * Class \Ess\M2ePro\Model\VersionsHistory
 */
class VersionsHistory extends ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\VersionsHistory');
    }

    //########################################

    public function getVersionFrom()
    {
        return $this->getData('version_from');
    }

    public function getVersionTo()
    {
        return $this->getData('version_to');
    }

    public function getUpdateDate()
    {
        return $this->getData('update_date');
    }

    public function getCreateDate()
    {
        return $this->getData('create_date');
    }

    //########################################
}
