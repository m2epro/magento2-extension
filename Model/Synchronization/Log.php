<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Synchronization;

/**
 * @method \Ess\M2ePro\Model\ResourceModel\Synchronization\Log getResource()
 */
class Log extends \Ess\M2ePro\Model\Log\AbstractModel
{
    const TASK_UNKNOWN = 0;
    const _TASK_UNKNOWN = 'System';

    const TASK_GENERAL = 1;
    const _TASK_GENERAL = 'General Synchronization';
    const TASK_LISTINGS_PRODUCTS = 2;
    const _TASK_LISTINGS_PRODUCTS = 'Listings Products Synchronization';
    const TASK_TEMPLATES = 3;
    const _TASK_TEMPLATES = 'Inventory Synchronization';
    const TASK_ORDERS = 4;
    const _TASK_ORDERS = 'Orders Synchronization';
    const TASK_MARKETPLACES = 5;
    const _TASK_MARKETPLACES = 'Marketplaces Synchronization';
    const TASK_OTHER_LISTINGS = 6;
    const _TASK_OTHER_LISTINGS = '3rd Party Listings Synchronization';
    const TASK_POLICIES = 7;
    const _TASK_OTHER_POLICIES = 'Business Policies Synchronization';

    /**
     * @var null|int
     */
    private $operationHistoryId = NULL;

    /**
     * @var int
     */
    private $task = self::TASK_UNKNOWN;

    /**
     * @var int
     */
    protected $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Synchronization\Log');
    }

    //########################################

    /**
     * @param int $id
     */
    public function setOperationHistoryId($id)
    {
        $this->operationHistoryId = (int)$id;
    }

    /**
     * @param int $initiator
     */
    public function setInitiator($initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN)
    {
        $this->initiator = (int)$initiator;
    }

    /**
     * @param int $task
     */
    public function setSynchronizationTask($task = self::TASK_UNKNOWN)
    {
        $this->task = (int)$task;
    }

    //########################################

    public function addMessage($description = NULL, $type = NULL, $priority = NULL, array $additionalData = array())
    {
        $dataForAdd = $this->makeDataForAdd($description,
                                            $type,
                                            $priority,
                                            $additionalData);

        $this->createMessage($dataForAdd);
    }

    //########################################

    public function clearMessages($task = NULL)
    {
        $filters = array();

        if (!is_null($task)) {
            $filters['task'] = $task;
        }
        if (!is_null($this->componentMode)) {
            $filters['component_mode'] = $this->componentMode;
        }

        $this->getResource()->clearMessages($filters);
    }

    //########################################

    protected function createMessage($dataForAdd)
    {
        $dataForAdd['operation_history_id'] = $this->operationHistoryId;
        $dataForAdd['task'] = $this->task;
        $dataForAdd['initiator'] = $this->initiator;
        $dataForAdd['component_mode'] = $this->componentMode;

        $this->activeRecordFactory->getObject('Synchronization\Log')
            ->setData($dataForAdd)
            ->save()
            ->getId();
    }

    protected function makeDataForAdd($description = NULL, $type = NULL, $priority = NULL,
                                      array $additionalData = array())
    {
        $dataForAdd = array();

        if (!is_null($description)) {
            $dataForAdd['description'] = $this->getHelper('Module\Translation')->__($description);
        } else {
            $dataForAdd['description'] = NULL;
        }

        if (!is_null($type)) {
            $dataForAdd['type'] = (int)$type;
        } else {
            $dataForAdd['type'] = self::TYPE_NOTICE;
        }

        if (!is_null($priority)) {
            $dataForAdd['priority'] = (int)$priority;
        } else {
            $dataForAdd['priority'] = self::PRIORITY_LOW;
        }

        $dataForAdd['additional_data'] = $this->getHelper('Data')->jsonEncode($additionalData);

        return $dataForAdd;
    }

    //########################################
}