<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\ProcessActionsResults
 */
class ProcessActionsResults extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/listing/product/process_actions_results';

    //####################################

    protected function performActions()
    {
        $this->completeExpiredActions();
        $this->executeCompletedRequestsPendingSingle();
    }

    //####################################

    protected function completeExpiredActions()
    {
        /**
         * @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Action\Processing\Collection $actionCollection
         */
        $actionCollection = $this->activeRecordFactory->getObject('Amazon_Listing_Product_Action_Processing')
            ->getCollection();
        $actionCollection->addFieldToFilter('request_pending_single_id', ['notnull' => true]);
        $actionCollection->getSelect()->joinLeft(
            [
                'rps' => $this->activeRecordFactory->getObject('Request_Pending_Single')
                ->getResource()->getMainTable()
            ],
            'rps.id = main_table.request_pending_single_id',
            []
        );
        $actionCollection->addFieldToFilter('rps.id', ['null' => true]);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Processing[] $actions */
        $actions = $actionCollection->getItems();

        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
        $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            'Request wait timeout exceeded.',
            \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
        );

        foreach ($actions as $action) {
            $this->completeAction($action, ['messages' => [$message->asArray()]]);
        }
    }

    protected function executeCompletedRequestsPendingSingle()
    {
        $requestIds = $this->activeRecordFactory->getObject('Amazon_Listing_Product_Action_Processing')->getResource()
            ->getUniqueRequestPendingSingleIds();
        if (empty($requestIds)) {
            return;
        }

        $requestPendingSingleCollection = $this->activeRecordFactory->getObject('Request_Pending_Single')
            ->getCollection();
        $requestPendingSingleCollection->addFieldToFilter('id', ['in' => $requestIds]);
        $requestPendingSingleCollection->addFieldToFilter('is_completed', 1);

        /** @var \Ess\M2ePro\Model\Request\Pending\Single[] $requestPendingSingleObjects */
        $requestPendingSingleObjects = $requestPendingSingleCollection->getItems();
        if (empty($requestPendingSingleObjects)) {
            return;
        }

        foreach ($requestPendingSingleObjects as $requestId => $requestPendingSingle) {
            $actionCollection = $this->activeRecordFactory->getObject('Amazon_Listing_Product_Action_Processing')
                ->getCollection();
            $actionCollection->setRequestPendingSingleIdFilter($requestId);
            $actionCollection->setInProgressFilter();

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Processing[] $actions */
            $actions = $actionCollection->getItems();

            $resultData     = $requestPendingSingle->getResultData();
            $resultMessages = $requestPendingSingle->getResultMessages();

            foreach ($actions as $action) {
                $listingProductId = $action->getListingProductId();

                $resultActionData = $this->getResponseData($resultData, $listingProductId);
                $resultActionData['messages'] = $this->getResponseMessages(
                    $resultData,
                    $resultMessages,
                    $listingProductId
                );

                $this->completeAction($action, $resultActionData, $requestPendingSingle->getData('create_date'));
            }

            $requestPendingSingle->delete();
        }
    }

    //####################################

    protected function getResponseData(array $responseData, $listingProductId)
    {
        $data = [];

        if (!empty($responseData['asins'][$listingProductId.'-id'])) {
            $data['asins'] = $responseData['asins'][$listingProductId.'-id'];
        }

        return $data;
    }

    protected function getResponseMessages(array $responseData, array $responseMessages, $relatedId)
    {
        $messages = $responseMessages;

        if (!empty($responseData['messages'][0])) {
            $messages = array_merge($messages, $responseData['messages']['0']);
        }

        if (!empty($responseData['messages']['0-id'])) {
            $messages = array_merge($messages, $responseData['messages']['0-id']);
        }

        if (!empty($responseData['messages'][$relatedId.'-id'])) {
            $messages = array_merge($messages, $responseData['messages'][$relatedId.'-id']);
        }

        return $messages;
    }

    protected function completeAction(
        \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Processing $action,
        array $data,
        $requestTime = null
    ) {
        try {
            $processing = $action->getProcessing();

            $processing->setSettings('result_data', $data);
            $processing->setData('is_completed', 1);

            if ($requestTime !== null) {
                $processingParams = $processing->getParams();
                $processingParams['request_time'] = $requestTime;
                $processing->setSettings('params', $processingParams);
            }

            $processing->save();
        } catch (\Exception $exception) {
            $this->processTaskException($exception);
        }

        $action->delete();
    }

    //####################################
}
