<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Synchronization;

/**
 * @method \Ess\M2ePro\Model\ResourceModel\Synchronization\Log getResource()
 */
class Log extends \Ess\M2ePro\Model\Log\AbstractModel
{
    const TYPE_FATAL_ERROR = 100;

    const TASK_OTHER = 0;
    const _TASK_OTHER = 'Other';

    const TASK_LISTINGS = 2;
    const _TASK_LISTINGS = 'M2E Pro Listings';

    const TASK_OTHER_LISTINGS = 5;
    const _TASK_OTHER_LISTINGS = 'Unmanaged Listings';

    const TASK_ORDERS = 3;
    const _TASK_ORDERS = 'Orders';

    const TASK_MARKETPLACES = 4;
    const _TASK_MARKETPLACES = 'Marketplaces';

    const TASK_REPRICING = 6;
    const _TASK_REPRICING = 'Repricing';

    /**
     * @var null|int
     */
    private $operationHistoryId = null;

    /**
     * @var int
     */
    private $task = self::TASK_OTHER;

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
    public function setSynchronizationTask($task = self::TASK_OTHER)
    {
        $this->task = (int)$task;
    }

    //########################################

    public function addMessageFromException(\Exception $exception)
    {
        return $this->addMessage(
            $exception->getMessage(),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
            [],
            $this->getHelper('Module_Exception')->getExceptionDetailedInfo($exception)
        );
    }

    public function addMessage(
        $description = null,
        $type = null,
        array $additionalData = [],
        $detailedDescription = null
    ) {
        $dataForAdd = [
            'description'          => $description,
            'detailed_description' => $detailedDescription,
            'type'                 => (int)$type,
            'additional_data'      => $this->getHelper('Data')->jsonEncode($additionalData),

            'operation_history_id' => $this->operationHistoryId,
            'task'                 => $this->task,
            'initiator'            => $this->initiator,
            'component_mode'       => $this->componentMode,
        ];

        $this->activeRecordFactory->getObject('Synchronization\Log')
            ->setData($dataForAdd)
            ->save()
            ->getId();
    }

    public function clearMessages($task = null)
    {
        $filters = [];

        if ($task !== null) {
            $filters['task'] = $task;
        }
        if ($this->componentMode !== null) {
            $filters['component_mode'] = $this->componentMode;
        }

        $this->getResource()->clearMessages($filters);
    }

    //########################################

    public function setFatalErrorHandler()
    {
        $temp = $this->getHelper('Data_GlobalData')->getValue(__CLASS__.'-'.__METHOD__);
        if (!empty($temp)) {
            return;
        }

        $this->getHelper('Data_GlobalData')->setValue(__CLASS__.'-'.__METHOD__, true);

        $object = $this;
        // @codingStandardsIgnoreLine
        register_shutdown_function(
            function () use ($object) {
                $error = error_get_last();
                if ($error === null) {
                    return;
                }

                if (!in_array((int)$error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
                    return;
                }

                $trace = debug_backtrace(false);
                $traceInfo = $this->getHelper('Module_Exception')->getFatalStackTraceInfo($trace);

                $object->addMessage(
                    $error['message'],
                    $object::TYPE_FATAL_ERROR,
                    [],
                    $this->getHelper('Module_Exception')->getFatalErrorDetailedInfo($error, $traceInfo)
                );
            }
        );
    }

    //########################################
}
