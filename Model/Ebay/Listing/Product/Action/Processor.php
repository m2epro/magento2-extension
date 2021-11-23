<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action;

use Ess\M2ePro\Model\Connector\Connection\Response\Message;
use Ess\M2ePro\Model\Exception\Logic;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Processor\Processor
 */
class Processor extends \Ess\M2ePro\Model\AbstractModel
{
    const ACTION_MAX_LIFE_TIME = 86400;

    const MAX_PARALLEL_EXECUTION_PACK_SIZE = 10;

    const ONE_SERVER_CALL_INCREASE_TIME = 1;
    const MAX_TOTAL_EXECUTION_TIME = 180;

    const FIRST_CONNECTION_ERROR_DATE_REGISTRY_KEY = '/ebay/listing/product/action/first_connection_error/date/';

    /** @var \Magento\Framework\Event\ManagerInterface */
    protected $eventDispatcher;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    protected $ebayFactory;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resource;

    //####################################

    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventDispatcher,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->eventDispatcher = $eventDispatcher;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory = $ebayFactory;
        $this->resource = $resource;
    }

    //####################################

    public function process()
    {
        $this->removeMissedProcessingActions();
        $this->completeExpiredActions();

        $actions = $this->getActionsForExecute();
        if (empty($actions)) {
            return;
        }

        $serialExecutionTime = $this->calculateSerialExecutionTime($actions);

        if ($serialExecutionTime <= self::MAX_TOTAL_EXECUTION_TIME) {
            $this->executeSerial($actions);
        } else {
            $this->executeParallel($actions);
        }
    }

    //####################################

    protected function removeMissedProcessingActions()
    {
        $actionCollection = $this->activeRecordFactory->getObject('Ebay_Listing_Product_Action_Processing')
            ->getCollection();
        $actionCollection->getSelect()->joinLeft(
            [
                'p' => $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('m2epro_processing')
            ],
            'p.id = main_table.processing_id',
            []
        );
        $actionCollection->addFieldToFilter('p.id', ['null' => true]);

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Processing[] $actions */
        $actions = $actionCollection->getItems();

        foreach ($actions as $action) {
            try {
                $action->delete();
            } catch (\Exception $exception) {
                $this->getHelper('Module_Exception')->process($exception);
            }
        }
    }

    protected function completeExpiredActions()
    {
        $minimumAllowedDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $minimumAllowedDate->modify('- ' . self::ACTION_MAX_LIFE_TIME . ' seconds');

        $actionCollection = $this->activeRecordFactory->getObject('Ebay_Listing_Product_Action_Processing')
            ->getCollection();
        ;
        $actionCollection->addFieldToFilter('create_date', ['lt' => $minimumAllowedDate->format('Y-m-d H:i:s')]);

        /** @var Processing[] $expiredActions */
        $expiredActions = $actionCollection->getItems();

        if (empty($expiredActions)) {
            return;
        }

        $expiredMessage = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $expiredMessage->initFromPreparedData(
            'Request wait timeout exceeded.',
            Message::TYPE_ERROR
        );
        $expiredMessage = $expiredMessage->asArray();

        foreach ($expiredActions as $expiredAction) {
            try {
                $this->completeAction($expiredAction, [], [$expiredMessage]);
            } catch (\Exception $exception) {
                $this->getHelper('Module_Exception')->process($exception);
                $expiredAction->delete();
            }
        }
    }

    //####################################

    /**
     * @return Processing[]
     */
    protected function getActionsForExecute()
    {
        $actionCollection = $this->activeRecordFactory
            ->getObject('Ebay_Listing_Product_Action_Processing')
            ->getCollection();
        $limit = (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            '/ebay/listing/product/scheduled_actions/',
            'max_prepared_actions_count'
        );
        $actionCollection->getSelect()->order('id ASC')->limit($limit);

        $connRead = $this->resource->getConnection();
        $statement = $connRead->query($actionCollection->getSelect());

        $actions = [];

        while (($actionData = $statement->fetch()) !== false) {
            $action = $this->activeRecordFactory->getObject('Ebay_Listing_Product_Action_Processing');
            $action->setData($actionData);

            if ($this->isActionCanBeAdded($action, $actions)) {
                $actions[] = $action;
            }

            if ($this->isActionsSetFull($actions)) {
                break;
            }
        }

        return $actions;
    }

    //-----------------------------------------

    /**
     * @param Processing[] $actions
     * @throws Logic
     */
    protected function executeSerial(array $actions)
    {
        $dispatcher = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $percentsForOneAction = 100 / count($actions);

        foreach ($actions as $iteration => $action) {
            $command = $this->getCommand($action);

            /** @var \Ess\M2ePro\Model\Connector\Command\RealTime\Virtual $connector */
            $connector = $dispatcher->getVirtualConnector(
                $command[0],
                $command[1],
                $command[2],
                $action->getRequestData(),
                null,
                $action->getListingProduct()->getMarketplace()->getId(),
                $action->getListingProduct()->getAccount()->getId(),
                $action->getRequestTimeOut()
            );

            try {
                $dispatcher->process($connector);
            } catch (\Exception $exception) {
                $this->getHelper('Module\Exception')->process($exception);

                if ($exception instanceof \Ess\M2ePro\Model\Exception\Connection) {
                    $isRepeat = $exception->handleRepeatTimeout(self::FIRST_CONNECTION_ERROR_DATE_REGISTRY_KEY);
                    if ($isRepeat) {
                        return;
                    }
                }

                /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
                $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
                $message->initFromException($exception);

                $this->completeAction($action, [], [$message->asArray()]);

                continue;
            }

            $this->completeAction(
                $action,
                $connector->getResponseData(),
                $connector->getResponseMessages(),
                $connector->getRequestTime()
            );

            if ($iteration % 10 == 0) {

                $this->eventDispatcher->dispatch(
                    \Ess\M2ePro\Model\Cron\Strategy\AbstractModel::PROGRESS_SET_DETAILS_EVENT_NAME,
                    [
                        'progress_nick' => \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product\ProcessActions::NICK,
                        'percentage' => ceil($percentsForOneAction * $iteration),
                        'total' => count($actions)
                    ]
                );
            }
        }
    }

    /**
     * @param Processing[] $actions
     * @throws \Ess\M2ePro\Model\Exception
     */
    protected function executeParallel(array $actions)
    {
        $dispatcher = $this->modelFactory
            ->getObject('Ebay_Listing_Product_Action_Processor_Connector_Multiple_Dispatcher');

        $groups = $this->groupForParallelExecution($actions, true);

        $processedActions = 0;
        $percentsForOneAction = 100 / count($actions);

        foreach ($groups as $actionsPacks) {
            foreach ($actionsPacks as $actionsPack) {
                /** @var Processing[] $actionsPack */

                $connectors = [];

                foreach ($actionsPack as $action) {
                    try {
                        $command = $this->getCommand($action);

                        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
                        $ebayListingProduct = $action->getListingProduct()->getChildObject();

                        $connectors[$action->getId()] = $dispatcher->getCustomVirtualConnector(
                            'Ebay_Listing_Product_Action_Processor_Connector_Multiple_Command_VirtualWithoutCall',
                            $command[0],
                            $command[1],
                            $command[2],
                            $action->getRequestData(),
                            null,
                            $ebayListingProduct->getListing()->getMarketplaceId(),
                            $ebayListingProduct->getListing()->getAccountId(),
                            $action->getRequestTimeOut()
                        );

                        $processedActions++;
                    } catch (\Exception $exception) {
                        $this->getHelper('Module_Exception')->process($exception);
                        $action->delete();
                    }
                }

                if (empty($connectors)) {
                    continue;
                }

                $dispatcher->processMultiple($connectors, true);

                if ($processedActions % 10 == 0) {

                    $this->eventDispatcher->dispatch(
                        \Ess\M2ePro\Model\Cron\Strategy\AbstractModel::PROGRESS_SET_DETAILS_EVENT_NAME,
                        [
                            'progress_nick' => \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product\ProcessActions::NICK,
                            'percentage' => ceil($percentsForOneAction * $processedActions),
                            'total' => count($actions)
                        ]
                    );
                }

                $systemErrorsMessages = [];
                $isServerInMaintenanceMode = null;

                foreach ($connectors as $actionId => $connector) {
                    /** @var \Ess\M2ePro\Model\Connector\Command\AbstractModel $connector */
                    foreach ($actionsPack as $action) {
                        if ($action->getId() != $actionId) {
                            continue;
                        }

                        $response = $connector->getResponse();

                        if ($response->getMessages()->hasSystemErrorEntity()) {
                            $systemErrorsMessages[] = $response->getMessages()->getCombinedSystemErrorsString();

                            if ($isServerInMaintenanceMode === null && $response->isServerInMaintenanceMode()) {
                                $isServerInMaintenanceMode = true;
                            }
                            continue;
                        }

                        $this->completeAction(
                            $action,
                            $connector->getResponseData(),
                            $connector->getResponseMessages(),
                            $connector->getRequestTime()
                        );

                        break;
                    }
                }

                if (!empty($systemErrorsMessages)) {
                    throw new \Ess\M2ePro\Model\Exception(
                        $this->getHelper('Module\Translation')->__(
                            "Internal Server Error(s) [%error_message%]",
                            $this->getCombinedErrorMessage($systemErrorsMessages)
                        ),
                        [],
                        0,
                        !$isServerInMaintenanceMode
                    );
                }
            }
        }
    }

    //-----------------------------------------

    protected function getCombinedErrorMessage(array $systemErrorsMessages)
    {
        $combinedErrorMessages = [];
        foreach ($systemErrorsMessages as $systemErrorMessage) {
            $key = sha1($systemErrorMessage);

            if (isset($combinedErrorMessages[$key])) {
                $combinedErrorMessages[$key]["count"] += 1;
                continue;
            }

            $combinedErrorMessages[$key] = [
                "message" => $systemErrorMessage,
                "count" => 1
            ];
        }

        $message = "";
        foreach ($combinedErrorMessages as $combinedErrorMessage) {
            $message .= sprintf(
                "%s (%s)<br>",
                $combinedErrorMessage["message"],
                $combinedErrorMessage["count"]
            );
        }

        return $message;
    }

    //####################################

    /**
     * @param Processing $action
     * @param Processing[] $actions
     * @return bool
     */
    protected function isActionCanBeAdded(Processing $action, array $actions)
    {
        if ($this->calculateParallelExecutionTime($actions) < self::MAX_TOTAL_EXECUTION_TIME) {
            return true;
        }

        $groupedActions = $this->groupForParallelExecution($actions, false);
        $commandRequestTime = $this->getCommandRequestTime($this->getCommand($action));

        if (empty($groupedActions[$commandRequestTime])) {
            return false;
        }

        foreach ($groupedActions[$commandRequestTime] as $actionsGroup) {
            if (count($actionsGroup) < self::MAX_PARALLEL_EXECUTION_PACK_SIZE) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Processing[] $actions
     * @return bool
     */
    private function isActionsSetFull(array $actions)
    {
        if ($this->calculateParallelExecutionTime($actions) < self::MAX_TOTAL_EXECUTION_TIME) {
            return false;
        }

        foreach ($this->groupForParallelExecution($actions, false) as $actionsGroups) {
            foreach ($actionsGroups as $actionsGroup) {
                if (count($actionsGroup) < self::MAX_PARALLEL_EXECUTION_PACK_SIZE) {
                    return false;
                }
            }
        }

        return true;
    }

    //-----------------------------------------

    /**
     * @param Processing[] $actions
     * @return int
     */
    protected function calculateSerialExecutionTime(array $actions)
    {
        $totalTime = 0;

        foreach ($actions as $action) {
            $commandRequestTime = $this->getCommandRequestTime($this->getCommand($action));
            $totalTime += $commandRequestTime + self::ONE_SERVER_CALL_INCREASE_TIME;
        }

        return $totalTime;
    }

    /**
     * @param Processing[] $actions
     * @return int
     */
    protected function calculateParallelExecutionTime(array $actions)
    {
        $totalTime = 0;

        foreach ($this->groupForParallelExecution($actions, false) as $commandRequestTime => $actionsPacks) {
            $actionsPacksCount = count($actionsPacks);
            $totalTime += $actionsPacksCount * ($commandRequestTime + self::ONE_SERVER_CALL_INCREASE_TIME);
        }

        return $totalTime;
    }

    //-----------------------------------------

    /**
     * @param Processing[] $actions
     * @param bool $needDistribute
     * @return array
     */
    protected function groupForParallelExecution(array $actions, $needDistribute = false)
    {
        $groupedByTimeActions = [];

        foreach ($actions as $action) {
            $commandRequestTime = $this->getCommandRequestTime($this->getCommand($action));
            $groupedByTimeActions[$commandRequestTime][] = $action;
        }

        $resultGroupedActions = [];

        $totalSerialExecutionTime = $this->calculateSerialExecutionTime($actions);

        foreach ($groupedByTimeActions as $commandRequestTime => $groupActions) {
            $packSize = self::MAX_PARALLEL_EXECUTION_PACK_SIZE;

            if ($needDistribute) {
                $groupSerialExecutionTime = $this->calculateSerialExecutionTime($groupActions);
                $groupAllowedExecutionTime = (int)(
                    self::MAX_TOTAL_EXECUTION_TIME * $groupSerialExecutionTime / $totalSerialExecutionTime
                );
                if ($groupAllowedExecutionTime < $commandRequestTime) {
                    $groupAllowedExecutionTime = $commandRequestTime;
                }

                $packsCount = ceil(
                    $groupAllowedExecutionTime / ($commandRequestTime + self::ONE_SERVER_CALL_INCREASE_TIME)
                );
                $packSize = ceil(count($groupActions) / $packsCount);
            }

            $resultGroupedActions[$commandRequestTime] = array_chunk($groupActions, $packSize);
        }

        return $resultGroupedActions;
    }

    //####################################

    protected function getCommand(Processing $action)
    {
        switch ($action->getType()) {
            case Processing::TYPE_LIST:
                return ['item', 'add', 'single'];

            case Processing::TYPE_RELIST:
                return ['item', 'update', 'relist'];

            case Processing::TYPE_REVISE:
                return ['item', 'update', 'reviseManager'];

            case Processing::TYPE_STOP:
                return ['item', 'update', 'end'];

            default:
                throw new Logic('Unknown action type.');
        }
    }

    private function getCommandRequestTime($command)
    {
        switch ($command) {
            case ['item', 'add', 'single']:
            case ['item', 'update', 'relist']:
                return 3;

            case ['item', 'update', 'reviseManager']:
                return 4;

            case ['item', 'update', 'end']:
                return 1;

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown command.');
        }
    }

    //-----------------------------------------

    protected function completeAction(Processing $action, array $data, array $messages, $requestTime = null)
    {
        $processing = $action->getProcessing();

        $processing->setSettings('result_data', $data);
        $processing->setSettings('result_messages', $messages);
        $processing->setData('is_completed', 1);

        if ($requestTime !== null) {
            $processingParams = $processing->getParams();
            $processingParams['request_time'] = $requestTime;
            $processing->setSettings('params', $processingParams);
        }

        $processing->save();

        $action->delete();
    }

    //####################################
}
