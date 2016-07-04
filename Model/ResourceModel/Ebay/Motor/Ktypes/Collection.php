<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Motor\Ktypes;

// TODO NOT SUPPORTED FEATURES "ebay parts compatibility"

//class Collection extends Varien\Data\Collection\Db
//{
//    //########################################
//
//    public function __construct($idFieldName = NULL)
//    {
//        $connRead = Mage::getResourceModel('core/config')->getReadConnection();
//
//        parent::__construct($connRead);
//
//        if (!is_null($idFieldName)) {
//            $this->_idFieldName = $idFieldName;
//        }
//
//        $table = Mage::getSingleton('core/resource')->getTableName('m2epro_ebay_dictionary_motor_ktype');
//
//        $this->getSelect()->reset()->from(
//            array('main_table' => $table)
//        );
//    }
//
//    //########################################
//
//    public function getAllIds()
//    {
//        $idsSelect = clone $this->getSelect();
//        $idsSelect->reset(\Zend_Db_Select::LIMIT_COUNT);
//        $idsSelect->reset(\Zend_Db_Select::LIMIT_OFFSET);
//        $idsSelect->reset(\Zend_Db_Select::COLUMNS);
//
//        $idsSelect->columns($this->_idFieldName, 'main_table');
//        $idsSelect->limit(1000);
//
//        return $this->getConnection()->fetchCol($idsSelect);
//    }
//
//    //########################################
//}