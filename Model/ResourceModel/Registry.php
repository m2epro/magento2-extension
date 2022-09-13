<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel;

class Registry extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    // ----------------------------------------

    public function _construct()
    {
        $this->_init('m2epro_registry', 'id');
    }

    // ----------------------------------------

    /**
     * @param string $key
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteByKey(string $key): void
    {
        $this->getConnection()
             ->delete($this->getMainTable(), "`key` = '$key'");
    }
}
