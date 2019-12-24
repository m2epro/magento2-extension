<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Motor;

/**
 * Class \Ess\M2ePro\Model\Ebay\Motor\Filter
 */
class Filter extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Motor\Filter');
    }

    //########################################

    public function delete()
    {
        if (!parent::delete()) {
            return false;
        }

        $connection = $this->getResource()->getConnection();
        $filterGroupRelation = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_ebay_motor_filter_to_group');
        $connection->delete($filterGroupRelation, ['filter_id = ?' => $this->getId()]);

        return true;
    }

    //########################################

    /**
     * @return int
     */
    public function getTitle()
    {
        return (int)$this->getData('title');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getType()
    {
        return (int)$this->getData('type');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isTypeEpid()
    {
        return in_array($this->getType(), [
            \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_EPID_MOTOR,
            \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_EPID_UK,
            \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_EPID_DE,
        ]);
    }

    /**
     * @return bool
     */
    public function isTypeKtype()
    {
        return $this->getType() == \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_KTYPE;
    }

    //########################################

    public function getConditions($asObject = true)
    {
        if ($asObject) {
            return $this->getSettings('conditions');
        }
        return $this->getData('conditions');
    }

    //########################################

    public function getNote()
    {
        return $this->getData('note');
    }

    //########################################
}
