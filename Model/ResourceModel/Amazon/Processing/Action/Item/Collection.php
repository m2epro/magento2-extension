<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Processing\Action\Item;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractCollection
{
    // ########################################

    private $isActionDataJoined = false;

    // ########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            'Ess\M2ePro\Model\Amazon\Processing\Action\Item',
            'Ess\M2ePro\Model\ResourceModel\Amazon\Processing\Action\Item'
        );
    }

    // ########################################

    public function setActionFilter(\Ess\M2ePro\Model\Amazon\Processing\Action $action)
    {
        $this->addFieldToFilter('main_table.action_id', (int)$action->getId());
        return $this;
    }

    public function setRequestPendingSingleIdFilter($requestPendingSingleIds)
    {
        if (!is_array($requestPendingSingleIds)) {
            $requestPendingSingleIds = array($requestPendingSingleIds);
        }

        $this->addFieldToFilter('main_table.request_pending_single_id', array('in' => $requestPendingSingleIds));
        return $this;
    }

    public function setAccountFilter(\Ess\M2ePro\Model\Account $account)
    {
        $this->joinActionData();
        $this->addFieldToFilter('mapa.account_id', (int)$account->getId());

        return $this;
    }

    public function setNotProcessedFilter()
    {
        $this->addFieldToFilter('main_table.is_completed', 0);
        $this->addFieldToFilter('main_table.request_pending_single_id', array('null' => true));

        return $this;
    }

    public function setInProgressFilter()
    {
        $this->addFieldToFilter('main_table.is_completed', 0);
        $this->addFieldToFilter('main_table.request_pending_single_id', array('notnull' => true));

        return $this;
    }

    public function setActionTypeFilter($actionType)
    {
        $this->joinActionData();
        $this->addFieldToFilter('mapa.type', $actionType);

        return $this;
    }

    public function setCreatedBeforeFilter($minutes)
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $dateTime->modify('- '.(int)$minutes.' minutes');

        $this->addFieldToFilter('main_table.create_date', array('lt' => $dateTime->format('Y-m-d H:i:s')));

        return $this;
    }

    // ########################################

    private function joinActionData()
    {
        if ($this->isActionDataJoined) {
            return;
        }

        $mapaTable = $this->activeRecordFactory->getObject('Amazon\Processing\Action')->getResource()->getMainTable();

        $this->getSelect()->joinLeft(
            array('mapa' => $mapaTable),
            'main_table.action_id=mapa.id', array('type')
        );

        $this->isActionDataJoined = true;
    }

    // ########################################
}