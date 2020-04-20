<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product\Websites;

/**
 * Class \Ess\M2ePro\Model\Magento\Product\Websites\Update
 * @method \Ess\M2ePro\Model\ResourceModel\Magento\Product\Websites\Update getResource()
 */
class Update extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    const ACTION_ADD = 1;
    const ACTION_REMOVE = 2;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Magento\Product\Websites\Update');
    }

    //########################################

    public function getProductId()
    {
        return $this->getData('product_id');
    }

    //----------------------------------------

    public function getAction()
    {
        return $this->getData('action');
    }

    public function isActionAdd()
    {
        return (int)$this->getData('action') == self::ACTION_ADD;
    }

    public function isActionRemove()
    {
        return (int)$this->getData('action') == self::ACTION_REMOVE;
    }

    //----------------------------------------

    public function getWebsiteId()
    {
        return $this->getData('website_id');
    }

    public function getCreateDate()
    {
        return $this->getData('create_date');
    }

    //########################################
}
