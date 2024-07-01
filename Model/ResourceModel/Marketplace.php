<?php

namespace Ess\M2ePro\Model\ResourceModel;

class Marketplace extends ActiveRecord\Component\Parent\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_NATIVE_ID = 'native_id';
    public const COLUMN_TITLE = 'title';
    public const COLUMN_CODE = 'code';
    public const COLUMN_URL = 'url';
    public const COLUMN_STATUS = 'status';
    public const COLUMN_SORDER = 'sorder';
    public const COLUMN_GROUP_TITLE = 'group_title';
    public const COLUMN_COMPONENT_MODE = 'component_mode';

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_MARKETPLACE,
            self::COLUMN_ID
        );
    }

    /**
     * @param \Ess\M2ePro\Model\Marketplace $marketplace
     */
    public function isDictionaryExist($marketplace)
    {
        $connection = $this->getConnection();
        $tableName = null;

        switch ($marketplace->getComponentMode()) {
            case \Ess\M2ePro\Helper\Component\Ebay::NICK:
                $tableName = 'm2epro_ebay_dictionary_marketplace';
                break;
            case \Ess\M2ePro\Helper\Component\Amazon::NICK:
                $tableName = 'm2epro_amazon_dictionary_marketplace';
                break;
            case \Ess\M2ePro\Helper\Component\Walmart::NICK:
                $tableName = 'm2epro_walmart_dictionary_marketplace';
                break;
            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown component_mode');
        }

        $select = $connection
            ->select()
            ->from($this->getHelper('Module_Database_Structure')->getTableNameWithPrefix($tableName), 'id')
            ->where('marketplace_id = ?', $marketplace->getId());

        return $connection->fetchOne($select) !== false;
    }
}
