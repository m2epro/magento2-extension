<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\AccountPickupStore\Synchronize;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\AccountPickupStore\Synchronize\ProductsRequester
 */
class ProductsRequester extends \Ess\M2ePro\Model\Ebay\Connector\Command\Pending\Requester
{
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';

    //########################################

    /** @var \Ess\M2ePro\Model\Ebay\Account\PickupStore\State[] $pickupStoreStateItems */
    private $pickupStoreStateItems = [];

    private $requestData = [];

    /** @var \Ess\M2ePro\Model\Ebay\Account\PickupStore\Log $log */
    private $log = null;

    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Marketplace $marketplace,
        \Ess\M2ePro\Model\Account $account,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $params['logs_action_id'] = $this->getLog()->getResource()->getNextActionId();
        parent::__construct($marketplace, $account, $helperFactory, $modelFactory, $params);
    }

    //########################################

    public function setPickupStoreStateItems(array $items)
    {
        $this->pickupStoreStateItems = $items;
        return $this;
    }

    //########################################

    protected function getCommand()
    {
        return ['store', 'synchronize', 'products'];
    }

    protected function getRequestData()
    {
        if (!empty($this->requestData)) {
            return $this->requestData;
        }

        $requestData = [];

        foreach ($this->pickupStoreStateItems as $stateItem) {
            if (!isset($requestData[$stateItem->getSku()])) {
                $requestData[$stateItem->getSku()] = [
                    'sku'       => $stateItem->getSku(),
                    'locations' => [],
                ];
            }

            $locationData = [
                'sku'         => $stateItem->getSku(),
                'location_id' => $stateItem->getAccountPickupStore()->getLocationId(),
                'action'      => $stateItem->getIsDeleted() ? self::ACTION_DELETE : self::ACTION_UPDATE,
            ];

            if ($locationData['action'] == self::ACTION_UPDATE) {
                $locationData['qty'] = $stateItem->getTargetQty();
            }

            $requestData[$stateItem->getSku()]['locations'][] = $locationData;
        }

        return $this->requestData = ['items' => $requestData];
    }

    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Ebay_Connector_AccountPickupStore_Synchronize_ProcessingRunner';
    }

    protected function getProcessingParams()
    {
        return array_merge(
            parent::getProcessingParams(),
            [
                'pickup_store_state_ids' => array_keys($this->pickupStoreStateItems),
            ]
        );
    }

    protected function getResponserParams()
    {
        $stateItemsData = [];

        foreach ($this->pickupStoreStateItems as $id => $stateItem) {
            $stateItemsData[$id] = [
                'online_qty' => $stateItem->getOnlineQty(),
                'target_qty' => $stateItem->getTargetQty(),
                'is_added'   => $stateItem->getIsAdded(),
                'is_deleted' => $stateItem->getIsDeleted(),
            ];
        }

        return array_merge(
            parent::getResponserParams(),
            [
                'pickup_store_state_items' => $stateItemsData,
                'logs_action_id'           => $this->params['logs_action_id'],
            ]
        );
    }

    //########################################

    private function getLog()
    {
        if ($this->log !== null) {
            return $this->log;
        }

        return $this->log = $this->activeRecordFactory->getObject('Ebay_Account_PickupStore_Log');
    }

    //########################################
}
