<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Lock;

class Transactional extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Lock\Transactional');
    }

    //########################################

    /**
     * This object can NOT be locked. So we are avoiding unnecessary queries to the database.
     * @return bool
     */
    public function isLocked()
    {
        return false;
    }

    public function deleteProcessingLocks($tag = false, $processingId = false) {}

    //########################################

    public function getNick()
    {
        return $this->getData('nick');
    }

    public function getCreateDate()
    {
        return $this->getData('create_date');
    }

    //########################################
}