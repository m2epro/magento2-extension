<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

class ArchivedEntity extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\ArchivedEntity');
    }

    //########################################

    public function deleteProcessingLocks($tag = false, $processingId = false) {}

    //########################################

    public function getName()
    {
        return $this->getData('name');
    }

    public function getOriginId()
    {
        return $this->getData('origin_id');
    }

    public function getOriginData()
    {
        return $this->getData('data');
    }

    //########################################
}