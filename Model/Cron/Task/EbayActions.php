<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task;

use Ess\M2ePro\Model\Account;
use Ess\M2ePro\Model\Ebay\Processing\Action;
use Ess\M2ePro\Model\Connector\Connection\Response\Message;
use Ess\M2ePro\Model\Exception\Logic;

final class EbayActions extends AbstractTask
{
    const NICK = 'ebay_actions';
    const MAX_MEMORY_LIMIT = 512;

    const MAX_ACTIONS_COUNT = 500;
    const ACTION_MAX_LIFE_TIME = 86400;

    protected $ebayFactory;

    //####################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory
    )
    {
        parent::__construct($parentFactory, $modelFactory, $activeRecordFactory, $helperFactory, $resource);

        $this->ebayFactory = $ebayFactory;
    }

    //####################################

    protected function getNick()
    {
        return self::NICK;
    }

    protected function getMaxMemoryLimit()
    {
        return self::MAX_MEMORY_LIMIT;
    }

    //####################################

    protected function performActions()
    {
        $this->removeMissedProcessingActions();
        $this->completeSkippedSingleActions();
        $this->completeExpiredActions();

        $totalActionsCount = $this->getActionsCount();
        if ($totalActionsCount <= 0) {
            return;
        }

        $accountCollection = $this->ebayFactory->getObject('Account')->getCollection();
        /** @var Account[] $accounts */
        $accounts = $accountCollection->getItems();

        foreach ($accounts as $account) {
            $totalAccountActionsCount = $this->getActionsCount($account);
            if ($totalAccountActionsCount <= 0) {
                continue;
            }

            $accountActionsCountCoefficient = $totalAccountActionsCount * 100 / $totalActionsCount;

            $allowedAccountActionsCount = (int)(self::MAX_ACTIONS_COUNT * $accountActionsCountCoefficient / 100);
            if ($allowedAccountActionsCount <= 0) {
                continue;
            }

            $this->executeNotProcessedActions($account, $allowedAccountActionsCount);
        }
    }

    //####################################

    private function removeMissedProcessingActions()
    {
        $processingActionCollection = $this->activeRecordFactory->getObject('Ebay\Processing\Action')->getCollection();
        $processingActionCollection->getSelect()->joinLeft(
            array('p' => $this->resource->getTableName('m2epro_processing')),
            'p.id = main_table.processing_id',
            array()
        );
        $processingActionCollection->addFieldToFilter('p.id', array('null' => true));

        /** @var Action[] $processingActions */
        $processingActions = $processingActionCollection->getItems();
        if (empty($processingActions)) {
            return;
        }

        foreach ($processingActions as $processingAction) {
            $processingAction->delete();
        }
    }

    private function completeSkippedSingleActions()
    {
        $processingActionItemCollection = $this->activeRecordFactory->getObject('Ebay\Processing\Action\Item')
            ->getCollection();
        $processingActionItemCollection->setActionTypeFilter($this->getSingleActionTypes());
        $processingActionItemCollection->addFieldToFilter('is_skipped', true);

        /** @var Action\Item[] $skippedProcessingActionItems */
        $skippedProcessingActionItems = $processingActionItemCollection->getItems();
        if (empty($skippedProcessingActionItems)) {
            return;
        }

        foreach ($skippedProcessingActionItems as $skippedProcessingActionItem) {
            $this->completeAction(
                $skippedProcessingActionItem->getAction(), array(), array($this->getSkippedItemMessage())
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

    private function executeNotProcessedActions(Account $account, $maxActionsCount = NULL)
    {
        $processingActionCollection = $this->activeRecordFactory->getObject('Ebay\Processing\Action')->getCollection();
        $processingActionCollection->addFieldToFilter('account_id', $account->getId());
        !is_null($maxActionsCount) && $processingActionCollection->getSelect()->limit($maxActionsCount);

        /** @var Action[] $processingActions */
        $processingActions = $processingActionCollection->getItems();
        if (empty($processingActions)) {
            return;
        }

        foreach ($processingActions as $processingAction) {
            $this->getLockItem()->activate();

            if ($this->isActionTypeSingle($processingAction->getType())) {
                $this->executeSingleAction($processingAction);
            } else {
                $this->executeMultipleAction($processingAction);
            }
        }
    }

    //####################################

    private function executeSingleAction(Action $processingAction)
    {
        $command = $this->getCommand($processingAction->getType());

        /** @var Action\Item $processingActionItem */
        $processingActionItem = $processingAction->getItemCollection()->getFirstItem();

        if ($processingActionItem->isSkipped()) {
            $this->completeAction(
                $processingAction, array('is_skipped' => true), array($this->getSkippedItemMessage())
            );
            return;
        }

        $dispatcherObject = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');

        $connectorObj = $dispatcherObject->getVirtualConnector(
            $command[0], $command[1], $command[2],
            $processingActionItem->getInputData(), null,
            $processingAction->getMarketplaceId(), $processingAction->getAccountId(),
            $processingAction->getRequestTimeOut()
        );

        try {
            $dispatcherObject->process($connectorObj);
        } catch (\Exception $exception) {
            $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $message->initFromException($exception);

            $this->completeAction(
                $processingAction, array(), array($message->asArray()), $connectorObj->getRequestTime()
            );

            return;
        }

        $this->completeAction(
            $processingAction,
            $connectorObj->getResponseData(),
            $connectorObj->getResponseMessages(),
            $connectorObj->getRequestTime()
        );
    }

    private function executeMultipleAction(Action $processingAction)
    {
        /** @var Action\Item[] $processingActionItems */
        $processingActionItems = $processingAction->getItemCollection()->getItems();

        $dispatcherObject = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');

        $command = $this->getCommand($processingAction->getType());

        $requestData = array();
        $resultData  = array();

        foreach ($processingActionItems as $processingActionItem) {

            if ($processingActionItem->isSkipped()) {
                $resultData[$processingActionItem->getRelatedId()] = array(
                    'is_skipped' => true,
                    'messages'   => array($this->getSkippedItemMessage())
                );

                continue;
            }

            $requestData[$processingActionItem->getRelatedId()] = $processingActionItem->getInputData();
        }

        if (empty($requestData)) {
            $this->completeAction($processingAction, array('result' => $resultData), array());
            return;
        }

        $connectorObj = $dispatcherObject->getVirtualConnector(
            $command[0], $command[1], $command[2],
            array('items' => $requestData), null,
            $processingAction->getMarketplaceId(), $processingAction->getAccountId(),
            $processingAction->getRequestTimeOut()
        );

        try {
            $dispatcherObject->process($connectorObj);
        } catch (\Exception $exception) {

            $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $message->initFromException($exception);

            $this->completeAction(
                $processingAction, array(), array($message->asArray()), $connectorObj->getRequestTime()
            );

            return;
        }

        $this->completeAction(
            $processingAction,
            $connectorObj->getResponseData(),
            $connectorObj->getResponseMessages(),
            $connectorObj->getRequestTime()
        );
    }

    //####################################

    private function getActionsCount(Account $account = NULL)
    {
        $processingActionCollection = $this->activeRecordFactory->getObject('Ebay\Processing\Action')->getCollection();
        !is_null($account) && $processingActionCollection->addFieldToFilter('account_id', $account->getId());

        return $processingActionCollection->getSize();
    }

    private function getCommand($actionType)
    {
        switch ($actionType) {
            case Action::TYPE_LISTING_PRODUCT_LIST:
                return array('item', 'add', 'single');

            case Action::TYPE_LISTING_PRODUCT_REVISE:
                return array('item', 'update', 'revise');

            case Action::TYPE_LISTING_PRODUCT_RELIST:
                return array('item', 'update', 'relist');

            case Action::TYPE_LISTING_PRODUCT_STOP:
                return array('item', 'update', 'ends');

            default:
                throw new Logic('Unknown action type.');
        }
    }

    //####################################

    private function getSingleActionTypes()
    {
        return array(
            Action::TYPE_LISTING_PRODUCT_LIST,
            Action::TYPE_LISTING_PRODUCT_REVISE,
            Action::TYPE_LISTING_PRODUCT_RELIST,
        );
    }

    private function isActionTypeSingle($actionType)
    {
        return in_array($actionType, $this->getSingleActionTypes());
    }

    //####################################

    private function getSkippedItemMessage()
    {
        $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
        $message->initFromPreparedData(
            'The Action was skipped because the data on eBay channel were changed earlier.',
            Message::TYPE_ERROR
        );

        return $message->asArray();
    }

    private function completeAction(Action $action, array $data, array $messages, $requestTime = NULL)
    {
        $processing = $action->getProcessing();

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