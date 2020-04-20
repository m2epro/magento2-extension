<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\Action;

/**
 * Class \Ess\M2ePro\Model\Amazon\Order\Action\Processor
 */
class Processor extends \Ess\M2ePro\Model\AbstractModel
{
    const PENDING_REQUEST_MAX_LIFE_TIME = 86400;
    const MAX_ITEMS_PER_REQUEST = 10000;

    protected $amazonThrottlingManager;
    protected $amazonFactory;
    protected $activeRecordFactory;

    private $actionType = null;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Amazon\ThrottlingManager $amazonThrottlingManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = [],
        array $data = []
    ) {
        $this->amazonThrottlingManager = $amazonThrottlingManager;
        $this->amazonFactory = $amazonFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);

        if (empty($params['action_type'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Action Type is not defined.');
        }

        $this->actionType = $params['action_type'];
    }

    //########################################

    public function process()
    {
        $this->removeMissedProcessingActions();

        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountCollection */
        $accountCollection = $this->amazonFactory->getObject('Account')->getCollection();

        $merchantIds = [];

        /** @var \Ess\M2ePro\Model\Account $account */
        foreach ($accountCollection->getItems() as $account) {
            $merchantIds[] = $account->getChildObject()->getMerchantId();
        }

        $merchantIds = array_unique($merchantIds);
        if (empty($merchantIds)) {
            return;
        }

        foreach ($merchantIds as $merchantId) {
            $this->processAction($merchantId);
        }
    }

    //########################################

    protected function processAction($merchantId)
    {
        $processingActions = $this->getNotProcessedActions($merchantId);
        if (empty($processingActions)) {
            return;
        }

        if ($this->amazonThrottlingManager->getAvailableRequestsCount(
            $merchantId,
            \Ess\M2ePro\Model\Amazon\ThrottlingManager::REQUEST_TYPE_FEED
        ) < 1) {
            return;
        }

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
            $this->getHelper('Module_Exception')->process($exception);

            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromException($exception);

            foreach ($processingActions as $processingAction) {
                $this->completeProcessingAction($processingAction, ['messages' => [$message->asArray()]]);
            }

            return;
        }

        $this->amazonThrottlingManager->registerRequests(
            $merchantId,
            \Ess\M2ePro\Model\Amazon\ThrottlingManager::REQUEST_TYPE_FEED,
            1
        );

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
                'expiration_date' => $this->getHelper('Data')->getDate(
                    $this->getHelper('Data')->getCurrentGmtDate(true) + self::PENDING_REQUEST_MAX_LIFE_TIME
                )
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

    //########################################

    /**
     * @param $merchantId
     * @return \Ess\M2ePro\Model\Amazon\Order\Action\Processing[]
     */
    protected function getNotProcessedActions($merchantId)
    {
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

    protected function completeProcessingAction(\Ess\M2ePro\Model\Amazon\Order\Action\Processing $action, array $data)
    {
        $processing = $action->getProcessing();

        $processing->setSettings('result_data', $data);
        $processing->setData('is_completed', 1);

        $processing->save();

        $action->delete();
    }

    protected function getResponseMessages(array $responseData, array $responseMessages, $orderId)
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

    protected function getServerCommand()
    {
        switch ($this->actionType) {
            case \Ess\M2ePro\Model\Amazon\Order\Action\Processing::ACTION_TYPE_UPDATE:
                return ['orders', 'update', 'entities'];

            case \Ess\M2ePro\Model\Amazon\Order\Action\Processing::ACTION_TYPE_REFUND:
                return ['orders', 'refund', 'entities'];

            case \Ess\M2ePro\Model\Amazon\Order\Action\Processing::ACTION_TYPE_CANCEL:
                return ['orders', 'cancel', 'entities'];

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown action type');
        }
    }

    protected function getRequestDataKey()
    {
        switch ($this->actionType) {
            case \Ess\M2ePro\Model\Amazon\Order\Action\Processing::ACTION_TYPE_UPDATE:
                return 'items';

            case \Ess\M2ePro\Model\Amazon\Order\Action\Processing::ACTION_TYPE_REFUND:
            case \Ess\M2ePro\Model\Amazon\Order\Action\Processing::ACTION_TYPE_CANCEL:
                return 'orders';

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown action type');
        }
    }

    //########################################

    protected function removeMissedProcessingActions()
    {
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

    //########################################
}
