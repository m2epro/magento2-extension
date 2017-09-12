<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\AccountPickupStore\Synchronize;

class ProductsRequester extends \Ess\M2ePro\Model\Ebay\Connector\Command\Pending\Requester
{
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';

    //########################################

    /** @var \Ess\M2ePro\Model\Ebay\Account\PickupStore\State[] $pickupStoreStateItems */
    private $pickupStoreStateItems = array();

    private $requestData = array();

    /** @var \Ess\M2ePro\Model\Ebay\Account\PickupStore\Log $log */
    private $log = NULL;

    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Marketplace $marketplace,
        \Ess\M2ePro\Model\Account $account,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params
    )
    {
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
        return array('store', 'synchronize', 'products');
    }

    protected function getRequestData()
    {
        if (!empty($this->requestData)) {
            return $this->requestData;
        }

        $requestData = array();

        foreach ($this->pickupStoreStateItems as $stateItem) {
            if (!isset($requestData[$stateItem->getSku()])) {
                $requestData[$stateItem->getSku()] = array(
                    'sku'       => $stateItem->getSku(),
                    'locations' => array(),
                );
            }

            $locationData = array(
                'sku'         => $stateItem->getSku(),
                'location_id' => $stateItem->getAccountPickupStore()->getLocationId(),
                'action'      => $stateItem->getIsDeleted() ? self::ACTION_DELETE : self::ACTION_UPDATE,
            );

            if ($locationData['action'] == self::ACTION_UPDATE) {
                $locationData['qty'] = $stateItem->getTargetQty();
            }

            $requestData[$stateItem->getSku()]['locations'][] = $locationData;
        }

        return $this->requestData = array('items' => $requestData);
    }

    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Ebay\Connector\AccountPickupStore\Synchronize\ProcessingRunner';
    }

    protected function getProcessingParams()
    {
        return array_merge(
            parent::getProcessingParams(),
            array(
                'pickup_store_state_ids' => array_keys($this->pickupStoreStateItems),
            )
        );
    }

    protected function getResponserParams()
    {
        $stateItemsData = array();

        foreach ($this->pickupStoreStateItems as $id => $stateItem) {
            $stateItemsData[$id] = array(
                'online_qty' => $stateItem->getOnlineQty(),
                'target_qty' => $stateItem->getTargetQty(),
                'is_added'   => $stateItem->getIsAdded(),
                'is_deleted' => $stateItem->getIsDeleted(),
            );
        }

        return array_merge(
            parent::getResponserParams(),
            array(
                'pickup_store_state_items' => $stateItemsData,
                'logs_action_id'           => $this->params['logs_action_id'],
            )
        );
    }

    //########################################

    private function getLog()
    {
        if (!is_null($this->log)) {
            return $this->log;
        }

        return $this->log = $this->activeRecordFactory->getObject('Ebay\Account\PickupStore\Log');
    }

    //########################################
}