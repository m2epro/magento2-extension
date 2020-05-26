<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Template;

/**
 * Class \Ess\M2ePro\Model\Template\Synchronization
 */
class Synchronization extends \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel
{
    const QTY_MODE_NONE = 0;
    const QTY_MODE_LESS = 1;
    const QTY_MODE_BETWEEN = 2;
    const QTY_MODE_MORE = 3;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Template\Synchronization');
    }

    //########################################

    public function getTitle()
    {
        return $this->getData('title');
    }

    //########################################

    public function save($reloadOnCreate = false)
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('template_synchronization');
        return parent::save($reloadOnCreate);
    }

    public function delete()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('template_synchronization');
        return parent::delete();
    }

    //########################################

    public function getCacheGroupTags()
    {
        return array_merge(parent::getCacheGroupTags(), ['template']);
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}
