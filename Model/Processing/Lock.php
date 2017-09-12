<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Processing;

class Lock extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    //####################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Processing\Lock');
    }

    //####################################

    public function getProcessingId()
    {
        return (int)$this->getData('processing_id');
    }

    public function getModelName()
    {
        return $this->getData('model_name');
    }

    public function getObjectId()
    {
        return (int)$this->getData('object_id');
    }

    public function getTag()
    {
        return $this->getData('tag');
    }

    //####################################

    /**
     * This object can NOT be locked. So we are avoiding unnecessary queries to the database.
     * @return bool
     */
    public function isLocked()
    {
        return false;
    }

    public function deleteProcessingLocks($tag = false, $processingId = false) {}

    //####################################
}