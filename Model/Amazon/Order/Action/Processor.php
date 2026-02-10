<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\Action;

class Processor extends \Ess\M2ePro\Model\AbstractModel
{
    public const PENDING_REQUEST_MAX_LIFE_TIME = 86400;
    public const MAX_ITEMS_PER_REQUEST = 10000;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    private $amazonFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;
    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;
    /** @var mixed|null */
    private $actionType;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $helperModuleException;
    /** @var \Ess\M2ePro\Model\Amazon\Order\Action\TimeManager */
    private $timeManager;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Order\Action\TimeManager $timeManager,
        \Ess\M2ePro\Helper\Module\Exception $helperModuleException,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = [],
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->timeManager = $timeManager;
        $this->helperModuleException = $helperModuleException;
        $this->amazonFactory = $amazonFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->helperData = $helperData;

        if (empty($params['action_type'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Action Type is not defined.');
        }

        $this->actionType = $params['action_type'];
    }

    public function process(): void
    {
        $this->removeMissedProcessingActions();

        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountCollection */
        $accountCollection = $this->amazonFactory->getObject('Account')->getCollection();

        $merchantIds = [];

        /** @var \Ess\M2ePro\Model\Account $account */
        foreach ($accountCollection->getItems() as $account) {
            $merchantIds[] = $account->getChildObject()->getMerchantId();
        }

        if (empty($merchantIds)) {
            return;
        }

        $merchantIds = array_unique($merchantIds);

        foreach ($merchantIds as $merchantId) {
            $this->processAction($merchantId);
        }
    }

    protected function processAction(string $merchantId): void
    {
        if (!$this->isTimeToProcess($merchantId)) {
            return;
        }

        $processingActions = $this->getNotProcessedActions($merchantId);
        if (empty($processingActions)) {
            return;
        }

        $this->setLastProcessDate($merchantId);

        $requestDataKey = $this->getRequestDataKey();

        $requestData = [
            $requestDataKey => [],
            'accounts' => [],
        ];

        foreach ($processingActions as $processingAction) {
            $requestData[$requestDataKey][$processingAction->getOrderId()] = $processingAction->getRequestData();
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountCollection */
        $accountCollection = $this->amazonFactory->getObject('Account')->getCollection();
        $accountCollection->addFieldToFilter('merchant_id', $merchantId);

        /** @var \Ess\M2ePro\Model\Account[] $accounts */
        $accounts = $accountCollection->getItems();

        foreach ($accounts as $account) {
            /** @var \Ess\M2ePro\Model\Amazon\Account $amazonAccount */
            $amazonAccount = $account->getChildObject();
            $requestData['accounts'][] = $amazonAccount->getServerHash();
        }

        /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcher */
        $dispatcher = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');

        $command = $this->getServerCommand();

        $connector = $dispatcher->getVirtualConnector($command[0], $command[1], $command[2], $requestData, null, null);

        try {
            $dispatcher->process($connector);
        } catch (\Exception $exception) {
            $this->helperModuleException->process($exception);

            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromException($exception);

            foreach ($processingActions as $processingAction) {
                $this->completeProcessingAction($processingAction, ['messages' => [$message->asArray()]]);
            }

            return;
        }

        $responseData = $connector->getResponseData();
        $responseMessages = $connector->getResponseMessages();

        if (empty($responseData['processing_id'])) {
            foreach ($processingActions as $processingAction) {
                $messages = $this->getResponseMessages(
                    $responseData,
                    $responseMessages,
                    $processingAction->getOrderId()
                );
                $this->completeProcessingAction($processingAction, ['messages' => $messages]);
            }

            return;
        }

        /** @var \Ess\M2ePro\Model\Request\Pending\Single $requestPendingSingle */
        $requestPendingSingle = $this->activeRecordFactory->getObject('Request_Pending_Single');
        $requestPendingSingle->setData(
            [
                'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
                'server_hash' => $responseData['processing_id'],
                'expiration_date' => gmdate(
                    'Y-m-d H:i:s',
                    $this->helperData->getCurrentGmtDate(true)
                    + self::PENDING_REQUEST_MAX_LIFE_TIME
                ),
            ]
        );
        $requestPendingSingle->save();

        $actionsIds = [];
        foreach ($processingActions as $processingAction) {
            $actionsIds[] = $processingAction->getId();
        }

        $this->activeRecordFactory->getObject('Amazon_Order_Action_Processing')
                                  ->getResource()->markAsInProgress($actionsIds, $requestPendingSingle);
    }

    /**
     * @param $merchantId
     *
     * @return \Ess\M2ePro\Model\Amazon\Order\Action\Processing[]
     * @throws \Magento\Framework\Exception\LocalizedException|\Ess\M2ePro\Model\Exception\Logic
     */
    protected function getNotProcessedActions($merchantId): array
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Order\Action\Processing\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Amazon_Order_Action_Processing')->getCollection();
        $collection->getSelect()->joinLeft(
            ['o' => $this->activeRecordFactory->getObject('Order')->getResource()->getMainTable()],
            'o.id = main_table.order_id',
            []
        );
        $collection->getSelect()->joinLeft(
            ['aa' => $this->activeRecordFactory->getObject('Amazon\Account')->getResource()->getMainTable()],
            'aa.account_id = o.account_id',
            []
        );
        $collection->setNotProcessedFilter();
        $collection->addFieldToFilter('aa.merchant_id', $merchantId);
        $collection->addFieldToFilter('main_table.type', $this->actionType);
        $collection->getSelect()->limit(self::MAX_ITEMS_PER_REQUEST);

        return $collection->getItems();
    }

    protected function completeProcessingAction(Processing $action, array $data): void
    {
        $processing = $action->getProcessing();

        $processing->setSettings('result_data', $data);
        $processing->setData('is_completed', 1);

        $processing->save();

        $action->delete();
    }

    protected function getResponseMessages(array $responseData, array $responseMessages, int $orderId): array
    {
        $messages = $responseMessages;

        if (!empty($responseData['messages'][0])) {
            $messages = array_merge($messages, $responseData['messages']['0']);
        }

        if (!empty($responseData['messages']['0-id'])) {
            $messages = array_merge($messages, $responseData['messages']['0-id']);
        }

        if (!empty($responseData['messages'][$orderId . '-id'])) {
            $messages = array_merge($messages, $responseData['messages'][$orderId . '-id']);
        }

        return $messages;
    }

    private function isTimeToProcess(string $merchantId): bool
    {
        switch ($this->actionType) {
            case Processing::ACTION_TYPE_UPDATE:
                return $this->timeManager->isTimeToProcessUpdate($merchantId);

            case Processing::ACTION_TYPE_CANCEL:
                return $this->timeManager->isTimeToProcessCancel($merchantId);

            case Processing::ACTION_TYPE_REFUND:
                return $this->timeManager->isTimeToProcessRefund($merchantId);

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown action type');
        }
    }

    private function setLastProcessDate(string $merchantId): void
    {
        switch ($this->actionType) {
            case Processing::ACTION_TYPE_UPDATE:
                $this->timeManager->setLastUpdate($merchantId, \Ess\M2ePro\Helper\Date::createCurrentGmt());
                break;

            case Processing::ACTION_TYPE_CANCEL:
                $this->timeManager->setLastCancel($merchantId, \Ess\M2ePro\Helper\Date::createCurrentGmt());
                break;

            case Processing::ACTION_TYPE_REFUND:
                $this->timeManager->setLastRefund($merchantId, \Ess\M2ePro\Helper\Date::createCurrentGmt());
                break;

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown action type');
        }
    }

    protected function getServerCommand(): array
    {
        switch ($this->actionType) {
            case Processing::ACTION_TYPE_UPDATE:
                return ['orders', 'update', 'entities'];

            case Processing::ACTION_TYPE_REFUND:
                return ['orders', 'refund', 'entities'];

            case Processing::ACTION_TYPE_CANCEL:
                return ['orders', 'cancel', 'entities'];

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown action type');
        }
    }

    protected function getRequestDataKey(): string
    {
        switch ($this->actionType) {
            case Processing::ACTION_TYPE_UPDATE:
                return 'items';

            case Processing::ACTION_TYPE_REFUND:
            case Processing::ACTION_TYPE_CANCEL:
                return 'orders';

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown action type');
        }
    }

    protected function removeMissedProcessingActions(): void
    {
        /**
         * @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Action\Processing\Collection $actionCollection
         */
        $actionCollection = $this->activeRecordFactory->getObject('Amazon_Listing_Product_Action_Processing')
                                                      ->getCollection();
        $actionCollection->getSelect()->joinLeft(
            ['p' => $this->activeRecordFactory->getObject('Processing')->getResource()->getMainTable()],
            'p.id = main_table.processing_id',
            []
        );
        $actionCollection->addFieldToFilter('p.id', ['null' => true]);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Processing[] $actions */
        $actions = $actionCollection->getItems();

        foreach ($actions as $action) {
            $action->delete();
        }
    }
}
