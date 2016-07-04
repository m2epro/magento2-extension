<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Magento\Product;

class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    private $listingProductMode = false;

    //########################################

    public function setListingProductModeOn()
    {
        $this->listingProductMode = true;

        $this->_setIdFieldName('id');

        return $this;
    }

    //########################################

    public function getAllIds($limit = null, $offset = null)
    {
        if (!$this->listingProductMode) {
            return parent::getAllIds($limit, $offset);
        }

        // hack for selecting listing product ids instead entity ids
        $idsSelect = clone $this->getSelect();
        $idsSelect->reset(\Zend_Db_Select::ORDER);
        $idsSelect->reset(\Zend_Db_Select::LIMIT_COUNT);
        $idsSelect->reset(\Zend_Db_Select::LIMIT_OFFSET);

        $idsSelect->columns('lp.' . $this->getIdFieldName());
        $idsSelect->limit($limit, $offset);

        $data = $this->getConnection()->fetchAll($idsSelect, $this->_bindParams);

        $ids = array();
        foreach ($data as $row) {
            $ids[] = $row[$this->getIdFieldName()];
        }

        return $ids;
    }

    //########################################

    public function getSize()
    {
        if (is_null($this->_totalRecords)) {
            $this->_renderFilters();

            $countSelect = clone $this->getSelect();
            $countSelect->reset(\Zend_Db_Select::ORDER);
            $countSelect->reset(\Zend_Db_Select::LIMIT_COUNT);
            $countSelect->reset(\Zend_Db_Select::LIMIT_OFFSET);

            if ($this->listingProductMode) {
                $query = $countSelect->__toString();
                $query = <<<SQL
SELECT COUNT(temp_table.id) FROM ({$query}) temp_table
SQL;
            } else {
                $countSelect->reset(\Zend_Db_Select::GROUP);
                $query = $countSelect->__toString();
                $query = <<<SQL
SELECT COUNT(DISTINCT temp_table.entity_id) FROM ({$query}) temp_table
SQL;
            }

            $this->_totalRecords = $this->getConnection()->fetchOne($query, $this->_bindParams);
        }
        return intval($this->_totalRecords);
    }

    //########################################

    // Price Sorting Hack
    protected function _renderOrders()
    {
        if (!$this->_isOrdersRendered) {
            foreach ($this->_orders as $attribute => $direction) {
                if ($attribute == 'min_online_price' || $attribute == 'max_online_price') {
                    $this->getSelect()->order($attribute . ' ' . $direction);
                } else {
                    $this->addAttributeToSort($attribute, $direction);
                }
            }
            $this->_isOrdersRendered = true;
        }
        return $this;
    }

    //########################################
}