<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Processing\Action\Item;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractCollection
{
    // ########################################

    private $isActionDataJoined = false;

    // ########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            'Ess\M2ePro\Model\Ebay\Processing\Action\Item',
            'Ess\M2ePro\Model\ResourceModel\Ebay\Processing\Action\Item'
        );
    }

    // ########################################

    public function setActionFilter(\Ess\M2ePro\Model\Ebay\Processing\Action $action)
    {
        $this->addFieldToFilter('main_table.action_id', (int)$action->getId());
        return $this;
    }

    public function setAccountFilter(\Ess\M2ePro\Model\Account $account)
    {
        $this->joinActionData();
        $this->addFieldToFilter('mapa.account_id', (int)$account->getId());

        return $this;
    }

    public function setMarketplaceFilter(\Ess\M2ePro\Model\Marketplace $marketplace)
    {
        $this->joinActionData();
        $this->addFieldToFilter('mapa.marketplace_id', (int)$marketplace->getId());

        return $this;
    }

    public function setActionTypeFilter($actionType)
    {
        $this->joinActionData();

        if (is_array($actionType)) {
            $this->addFieldToFilter('mapa.type', array('in' => $actionType));
        } else {
            $this->addFieldToFilter('mapa.type', $actionType);
        }

        return $this;
    }

    // ########################################

    private function joinActionData()
    {
        if ($this->isActionDataJoined) {
            return;
        }

        $mapaTable = $this->activeRecordFactory->getObject('Ebay\Processing\Action')->getResource()->getMainTable();

        $this->getSelect()->joinLeft(
            array('mapa' => $mapaTable),
            'main_table.action_id=mapa.id', array('type')
        );

        $this->isActionDataJoined = true;
    }

    // ########################################
}