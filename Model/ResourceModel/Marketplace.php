<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Marketplace
 */
class Marketplace extends ActiveRecord\Component\Parent\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_marketplace', 'id');
    }

    //########################################

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

    //########################################
}
