<?php

namespace Ess\M2ePro\Model\ResourceModel;

class Registry extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public function _construct(): void
    {
        $this->_init(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_REGISTRY, 'id');
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
