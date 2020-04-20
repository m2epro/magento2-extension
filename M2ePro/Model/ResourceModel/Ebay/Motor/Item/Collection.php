<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Motor\Item;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Ebay\Motor\Item\Collection
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\Collection\Wrapper
{
    protected $scope;

    //########################################

    public function setIdFieldName($idFieldName)
    {
        $this->_idFieldName = $idFieldName;
    }

    public function setScope($scope)
    {
        $this->scope = $scope;

        if ($this->scope !== null) {
            $this->getSelect()->where('scope = ?', $scope);
        }
    }

    //########################################

    public function getAllIds()
    {
        $idsSelect = clone $this->getSelect();
        $idsSelect->reset(\Zend_Db_Select::LIMIT_COUNT);
        $idsSelect->reset(\Zend_Db_Select::LIMIT_OFFSET);
        $idsSelect->reset(\Zend_Db_Select::COLUMNS);

        $idsSelect->columns($this->_idFieldName, 'main_table');
        $idsSelect->limit(\Ess\M2ePro\Helper\Component\Ebay\Motors::MAX_ITEMS_COUNT_FOR_ATTRIBUTE);

        return $this->getConnection()->fetchCol($idsSelect);
    }

    //########################################
}
