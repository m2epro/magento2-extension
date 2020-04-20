<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Account\PickupStore;

/**
 * Class \Ess\M2ePro\Model\Ebay\Account\PickupStore\Log
 */
class Log extends \Ess\M2ePro\Model\Log\AbstractModel
{
    const ACTION_UNKNOWN = 1;
    const _ACTION_UNKNOWN = 'System';

    const ACTION_ADD_PRODUCT  = 2;
    const _ACTION_ADD_PRODUCT = 'Assign Product to the Store';

    const ACTION_DELETE_PRODUCT  = 3;
    const _ACTION_DELETE_PRODUCT = 'Unassign Product to the Store';

    const ACTION_UPDATE_QTY  = 4;
    const _ACTION_UPDATE_QTY = 'Change of Product QTY in Magento Store';

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Account\PickupStore\Log');
    }

    //########################################

    public function addMessage(
        $accountPickupStoreStateId,
        $actionId = null,
        $action = null,
        $description = null,
        $type = null,
        $priority = null
    ) {
        $dataForAdd = $this->makeDataForAdd(
            $accountPickupStoreStateId,
            $actionId,
            $action,
            $description,
            $type,
            $priority
        );

        $this->createMessage($dataForAdd);
    }

    //########################################

    protected function createMessage($dataForAdd)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Account\PickupStore\State $accountPickupStoreState */
        $accountPickupStoreState = $this->activeRecordFactory->getObjectLoaded(
            'Ebay_Account_PickupStore_State',
            $dataForAdd['account_pickup_store_state_id']
        );

        $accountPickupStore = $accountPickupStoreState->getAccountPickupStore();

        $dataForAdd['location_id']    = $accountPickupStore->getLocationId();
        $dataForAdd['location_title'] = $accountPickupStore->getName();

        $this->activeRecordFactory->getObject('Ebay_Account_PickupStore_Log')
            ->setData($dataForAdd)
            ->save();
    }

    protected function makeDataForAdd(
        $accountPickupStoreStateId,
        $actionId = null,
        $action = null,
        $description = null,
        $type = null,
        $priority = null,
        array $additionalData = []
    ) {
        $dataForAdd = [];

        $dataForAdd['account_pickup_store_state_id'] = (int)$accountPickupStoreStateId;

        if ($actionId !== null) {
            $dataForAdd['action_id'] = (int)$actionId;
        } else {
            $dataForAdd['action_id'] = $this->getResource()->getNextActionId();
        }

        if ($action !== null) {
            $dataForAdd['action'] = (int)$action;
        } else {
            $dataForAdd['action'] = self::ACTION_UNKNOWN;
        }

        if ($description !== null) {
            $dataForAdd['description'] = $description;
        } else {
            $dataForAdd['description'] = null;
        }

        if ($type !== null) {
            $dataForAdd['type'] = (int)$type;
        } else {
            $dataForAdd['type'] = self::TYPE_NOTICE;
        }

        if ($priority !== null) {
            $dataForAdd['priority'] = (int)$priority;
        } else {
            $dataForAdd['priority'] = self::PRIORITY_LOW;
        }

        $dataForAdd['additional_data'] = $this->getHelper('Data')->jsonEncode($additionalData);

        return $dataForAdd;
    }

    //########################################
}
