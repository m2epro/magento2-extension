<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Actions;

use Ess\M2ePro\Model\Ebay\Processing\Action;
use Ess\M2ePro\Model\Connector\Connection\Response\Message;
use Ess\M2ePro\Model\Exception\Logic;

class Processor extends \Ess\M2ePro\Model\AbstractModel
{
    const ACTION_MAX_LIFE_TIME = 86400;

    /**
     * Maximum actions that we can execute during MAX_TOTAL_EXECUTION_TIME (180 sec).
     * Fastest action (STOP (1 sec)), executed in parallel in packs of MAX_PARALLEL_EXECUTION_PACK_SIZE (10)
     * considering with ONE_SERVER_CALL_INCREASE_TIME (1 sec), calculated as following:
     *
     * MAX_TOTAL_EXECUTION_TIME /
     * (STOP_COMMAND_REQUEST_TIME + ONE_SERVER_CALL_INCREASE_TIME) * MAX_PARALLEL_EXECUTION_PACK_SIZE + 100 (buffer)
     */
    const MAX_SELECT_ACTIONS_COUNT = 1000;

    const MAX_PARALLEL_EXECUTION_PACK_SIZE = 10;

    const ONE_SERVER_CALL_INCREASE_TIME = 1;
    const MAX_TOTAL_EXECUTION_TIME      = 180;

    /** @var \Ess\M2ePro\Model\Lock\Item\Manager|null */
    private $lockItem = NULL;

    protected $activeRecordFactory;

    protected $ebayFactory;

    protected $resource;

    //####################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        $data = []
    )
    {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory         = $ebayFactory;
        $this->resource            = $resource;
    }

    //####################################

    public function getLockItem()
    {
        return $this->lockItem;
    }

    public function setLockItem(\Ess\M2ePro\Model\Lock\Item\Manager $lockItem)
    {
        $this->lockItem = $lockItem;
        return $this;
    }

    //####################################

    public function process()
    {
        $this->removeMissedProcessingActions();
        $this->completeNeedSynchRulesCheckActions();
        $this->completeExpiredActions();

        $actions = $this->getActionsForExecute();

        if ($this->calculateSerialExecutionTime($actions) <= self::MAX_TOTAL_EXECUTION_TIME) {
            $this->executeSerial($actions);
        } else {
            $this->executeParallel($actions);
        }
    }

    //####################################

    private function removeMissedProcessingActions()
    {
        $actionCollection = $this->activeRecordFactory->getObject('Ebay\Processing\Action')->getCollection();
        $actionCollection->getSelect()->joinLeft(
            array('p' => $this->resource->getTableName('m2epro_processing')),
            'p.id = main_table.processing_id',
            array()
        );
        $actionCollection->addFieldToFilter('p.id', array('null' => true));

        /** @var Action[] $actions */
        $actions = $actionCollection->getItems();
        if (empty($actions)) {
            return;
        }

        foreach ($actions as $action) {
            $action->delete();
        }
    }

    private function completeNeedSynchRulesCheckActions()
    {
        $actionCollection = $this->activeRecordFactory->getObject('Ebay\Processing\Action')->getCollection();
        $actionCollection->getSelect()->joinLeft(
            array('lp' => $this->resource->getTableName('m2epro_listing_product')),
            'lp.id = main_table.related_id',
            'need_synch_rules_check'
        );
        $actionCollection->addFieldToFilter('need_synch_rules_check', true);

        /** @var Action[] $actions */
        $actions = $actionCollection->getItems();
        if (empty($actions)) {
            return;
        }

        foreach ($actions as $action) {
            $this->completeAction(
                $action, array(), array($this->getNeedSynchRulesCheckActionMessage())
            );
        }
    }

    private function completeExpiredActions()
    {
        $minimumAllowedDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $minimumAllowedDate->modify('- '.self::ACTION_MAX_LIFE_TIME.' seconds');

        $actionCollection = $this->activeRecordFactory->getObject('Ebay\Processing\Action')->getCollection();;
        $actionCollection->addFieldToFilter('create_date', array('lt' => $minimumAllowedDate->format('Y-m-d H:i:s')));

        /** @var Action[] $expiredActions */
        $expiredActions = $actionCollection->getItems();
        if (empty($expiredActions)) {
            return;
        }

        $expiredMessage = $this->modelFactory->getObject('Connector\Connection\Response\Message');
        $expiredMessage->initFromPreparedData(
            'Request wait timeout exceeded.', Message::TYPE_ERROR
        );
        $expiredMessage = $expiredMessage->asArray();

        foreach ($expiredActions as $expiredAction) {
            $this->completeAction($expiredAction, array(), array($expiredMessage));
        }
    }

    //####################################

    /**
     * @return Action[]
     */
    private function getActionsForExecute()
    {
        $actionCollection = $this->activeRecordFactory->getObject('Ebay\Processing\Action')->getCollection();
        $actionCollection->getSelect()->order('priority DESC');
        $actionCollection->getSelect()->order('start_date ASC');
        $actionCollection->getSelect()->limit(self::MAX_SELECT_ACTIONS_COUNT);

        $connRead = $this->resource->getConnection('core_read');
        $statement = $connRead->query($actionCollection->getSelect());

        $actions = array();

        while (($actionData = $statement->fetch()) !== false) {
            $action = $this->activeRecordFactory->getObject('Ebay\Processing\Action');
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
     * @param Action[] $actions
     */
    private function executeSerial(array $actions)
    {
        $dispatcher = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');

        foreach ($actions as $action) {
            $this->getLockItem()->activate();

            $listingProduct = $this->ebayFactory->getObjectLoaded(
                'Listing\Product', $action->getRelatedId(), 'id', false
            );

            if ($listingProduct->getId() && $listingProduct->needSynchRulesCheck()) {
                $this->completeAction($action, array(), array($this->getNeedSynchRulesCheckActionMessage()));
                continue;
            }

            $command = $this->getCommand($action);

            /** @var \Ess\M2ePro\Model\Connector\Command\RealTime\Virtual $connector */
            $connector = $dispatcher->getVirtualConnector(
                $command[0], $command[1], $command[2],
                $action->getRequestData(), NULL,
                $action->getMarketplaceId(), $action->getAccountId(),
                $action->getRequestTimeOut()
            );

            $dispatcher->process($connector);

            $this->completeAction(
                $action,
                $connector->getResponseData(), $connector->getResponseMessages(),
                $connector->getRequestTime()
            );
        }
    }

    /**
     * @param Action[] $actions
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function executeParallel(array $actions)
    {
        $dispatcher = $this->modelFactory->getObject('Ebay\Actions\Processor\Connector\Multiple\Dispatcher');

        foreach ($this->groupForParallelExecution($actions, true) as $actionsPacks) {
            foreach ($actionsPacks as $actionsPack) {
                /** @var Action[] $actionsPack */

                $this->getLockItem()->activate();

                $listingsProducts = $this->getListingsProducts($actionsPack);

                /** @var Processor\Connector\Multiple\Command\VirtualWithoutCall[] $connectors */
                $connectors = array();

                foreach ($actionsPack as $action) {
                    if (isset($listingsProducts[$action->getRelatedId()]) &&
                        $listingsProducts[$action->getRelatedId()]->needSynchRulesCheck()) {
                        $this->completeAction($action, array(), array($this->getNeedSynchRulesCheckActionMessage()));
                        continue;
                    }

                    $command = $this->getCommand($action);

                    $connectors[$action->getId()] = $dispatcher->getCustomVirtualConnector(
                        'Ebay\Actions\Processor\Connector\Multiple\Command\VirtualWithoutCall',
                        $command[0], $command[1], $command[2],
                        $action->getRequestData(), NULL,
                        $action->getMarketplaceId(), $action->getAccountId(),
                        $action->getRequestTimeOut()
                    );
                }

                if (empty($connectors)) {
                    continue;
                }

                $dispatcher->processMultiple($connectors, true);

                $systemErrorsMessages = array();
                $isServerInMaintenanceMode = NULL;

                foreach ($connectors as $actionId => $connector) {
                    foreach ($actionsPack as $action) {
                        if ($action->getId() != $actionId) {
                            continue;
                        }

                        $response = $connector->getResponse();

                        if ($response->getMessages()->hasSystemErrorEntity()) {
                            $systemErrorsMessages[] = $response->getMessages()->getCombinedSystemErrorsString();

                            if (is_null($isServerInMaintenanceMode) && $response->isServerInMaintenanceMode()) {
                                $isServerInMaintenanceMode = true;
                            }
                            continue;
                        }

                        $this->completeAction(
                            $action,
                            $connector->getResponseData(), $connector->getResponseMessages(),
                            $connector->getRequestTime()
                        );

                        break;
                    }
                }

                if (!empty($systemErrorsMessages)) {
                    throw new \Ess\M2ePro\Model\Exception($this->getHelper('Module\Translation')->__(
                        "Internal Server Error(s) [%error_message%]",
                        $this->getCombinedErrorMessage($systemErrorsMessages)
                    ), array(), 0, !$isServerInMaintenanceMode);
                }
            }
        }
    }

    //-----------------------------------------

    private function getCombinedErrorMessage(array $systemErrorsMessages)
    {
        $combinedErrorMessages = array();
        foreach ($systemErrorsMessages as $systemErrorMessage) {
            $key = md5($systemErrorMessage);

            if (isset($combinedErrorMessages[$key])) {
                $combinedErrorMessages[$key]["count"] += 1;
                continue;
            }

            $combinedErrorMessages[$key] = array(
                "message" => $systemErrorMessage,
                "count" => 1
            );
        }

        $message = "";
        foreach ($combinedErrorMessages as $combinedErrorMessage) {
            $message .= sprintf("%s (%s)<br>",
                $combinedErrorMessage["message"],
                $combinedErrorMessage["count"]
            );
        }

        return $message;
    }

    //####################################

    /**
     * @param Action $action
     * @param Action[] $actions
     * @return bool
     */
    private function isActionCanBeAdded(Action $action, array $actions)
    {
        if ($this->calculateParallelExecutionTime($actions) < self::MAX_TOTAL_EXECUTION_TIME) {
            return true;
        }

        $groupedActions     = $this->groupForParallelExecution($actions, false);
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
     * @param Action[] $actions
     * @return bool
     */
    private function isActionsSetFull(array $actions)
    {
        if ($this->calculateParallelExecutionTime($actions) < self::MAX_TOTAL_EXECUTION_TIME) {
            return false;
        }

        foreach($this->groupForParallelExecution($actions, false) as $actionsGroups) {
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
     * @param Action[] $actions
     * @return int
     */
    private function calculateSerialExecutionTime(array $actions)
    {
        $totalTime = 0;

        foreach ($actions as $action) {
            $commandRequestTime = $this->getCommandRequestTime($this->getCommand($action));
            $totalTime += $commandRequestTime + self::ONE_SERVER_CALL_INCREASE_TIME;
        }

        return $totalTime;
    }

    /**
     * @param Action[] $actions
     * @return int
     */
    private function calculateParallelExecutionTime(array $actions)
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
     * @param Action[] $actions
     * @param bool $needDistribute
     * @return array
     */
    private function groupForParallelExecution(array $actions, $needDistribute = false)
    {
        $groupedByTimeActions = array();

        foreach ($actions as $action) {
            $commandRequestTime = $this->getCommandRequestTime($this->getCommand($action));
            $groupedByTimeActions[$commandRequestTime][] = $action;
        }

        $resultGroupedActions = array();

        $totalSerialExecutionTime = $this->calculateSerialExecutionTime($actions);

        foreach ($groupedByTimeActions as $commandRequestTime => $groupActions) {

            $packSize = self::MAX_PARALLEL_EXECUTION_PACK_SIZE;

            if ($needDistribute) {
                $groupSerialExecutionTime  = $this->calculateSerialExecutionTime($groupActions);
                $groupAllowedExecutionTime = (int)(
                    self::MAX_TOTAL_EXECUTION_TIME * $groupSerialExecutionTime / $totalSerialExecutionTime
                );
                if ($groupAllowedExecutionTime < $commandRequestTime) {
                    $groupAllowedExecutionTime = $commandRequestTime;
                }

                $packsCount = ceil(
                    $groupAllowedExecutionTime / ($commandRequestTime + self::ONE_SERVER_CALL_INCREASE_TIME)
                );
                $packSize   = ceil(count($groupActions) / $packsCount);
            }

            $resultGroupedActions[$commandRequestTime] = array_chunk($groupActions, $packSize);
        }

        return $resultGroupedActions;
    }

    /**
     * @param Action[] $actions
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    public function getListingsProducts(array $actions)
    {
        $listingsProductsIds = array();
        foreach ($actions as $action) {
            $listingsProductsIds[] = $action->getRelatedId();
        }

        $listingProductCollection = $this->ebayFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('id', array('in' => $listingsProductsIds));

        return $listingProductCollection->getItems();
    }

    //####################################

    private function getCommand(\Ess\M2ePro\Model\Ebay\Processing\Action $action)
    {
        switch ($action->getType()) {
            case Action::TYPE_LISTING_PRODUCT_LIST:
                return array('item', 'add', 'single');

            case Action::TYPE_LISTING_PRODUCT_REVISE:
                return array('item', 'update', 'revise');

            case Action::TYPE_LISTING_PRODUCT_RELIST:
                return array('item', 'update', 'relist');

            case Action::TYPE_LISTING_PRODUCT_STOP:
                return array('item', 'update', 'end');

            default:
                throw new Logic('Unknown action type.');
        }
    }

    private function getCommandRequestTime($command)
    {
        switch ($command) {
            case array('item', 'add', 'single'):
            case array('item', 'update', 'relist'):
                return 3;

            case array('item', 'update', 'revise'):
                return 4;

            case array('item', 'update', 'end'):
                return 1;

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown command.');
        }
    }

    //-----------------------------------------

    private function getNeedSynchRulesCheckActionMessage()
    {
        $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
        $message->initFromPreparedData(
            'There was no need for this action. It was skipped. New action request with updated Product
            information will be performed automatically.',
            \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
        );

        return $message->asArray();
    }

    private function completeAction(Action $action, array $data, array $messages, $requestTime = NULL)
    {
        $processing = $action->getProcessing();

        $data['start_processing_date'] = $action->getStartDate();

        $processing->setSettings('result_data', $data);
        $processing->setSettings('result_messages', $messages);
        $processing->setData('is_completed', 1);

        if (!is_null($requestTime)) {
            $processingParams = $processing->getParams();
            $processingParams['request_time'] = $requestTime;
            $processing->setSettings('params', $processingParams);
        }

        $processing->save();

        $action->delete();
    }

    //####################################
}