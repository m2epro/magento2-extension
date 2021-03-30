<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product;

use Ess\M2ePro\Model\Walmart\Listing\Product\Action\Processing as ActionProcessing;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\ProcessActionsResults
 */
class ProcessActionsResults extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'walmart/listing/product/process_actions_results';

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
         * @var \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product\Action\Processing\Collection $actionCollection
         */
        $actionCollection = $this->activeRecordFactory->getObject('Walmart_Listing_Product_Action_Processing')
            ->getCollection();
        $actionCollection->addFieldToFilter('request_pending_single_id', ['notnull' => true]);
        $actionCollection->addFieldToFilter('type', ['neq' => ActionProcessing::TYPE_ADD]);
        $actionCollection->getSelect()->joinLeft(
            [
                'rps' => $this->activeRecordFactory->getObject('Request_Pending_Single')
                    ->getResource()->getMainTable()
            ],
            'rps.id = main_table.request_pending_single_id',
            []
        );
        $actionCollection->addFieldToFilter('rps.id', ['null' => true]);

        /** @var ActionProcessing[] $actions */
        $actions = $actionCollection->getItems();

        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
        $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            'Request wait timeout exceeded.',
            \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
        );

        foreach ($actions as $action) {
            $this->completeAction($action, ['errors' => [$message->asArray()]]);
        }
    }

    protected function executeCompletedRequestsPendingSingle()
    {
        $requestIds = $this->activeRecordFactory->getObject('Walmart_Listing_Product_Action_Processing')->getResource()
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
            $actionCollection = $this->activeRecordFactory->getObject('Walmart_Listing_Product_Action_Processing')
                ->getCollection();
            $actionCollection->setRequestPendingSingleIdFilter($requestId);
            $actionCollection->setInProgressFilter();
            $actionCollection->addFieldToFilter('type', ['neq' => ActionProcessing::TYPE_ADD]);

            /** @var ActionProcessing[] $actions */
            $actions = $actionCollection->getItems();
            if (empty($actions)) {
                continue;
            }

            $resultData     = $requestPendingSingle->getResultData();
            $resultMessages = $requestPendingSingle->getResultMessages();

            foreach ($actions as $action) {
                $resultActionData = [
                    'errors' => []
                ];

                //worker may return different data structure
                if (isset($resultData[$action->getListingProductId() . '-id'])) {
                    $resultActionData = $resultData[$action->getListingProductId() . '-id'];
                } elseif (isset($resultData['data'][$action->getListingProductId() . '-id'])) {
                    $resultActionData = $resultData['data'][$action->getListingProductId() . '-id'];
                }

                if (!empty($resultMessages)) {
                    $errors = [];
                    if (!empty($resultActionData['errors'])) {
                        $errors = $resultActionData['errors'];
                    }

                    $resultActionData['errors'] = array_merge($errors, $resultMessages);
                }

                $this->completeAction($action, $resultActionData, $requestPendingSingle->getData('create_date'));
            }

            $requestPendingSingle->delete();
        }
    }

    //####################################

    protected function completeAction(ActionProcessing $action, array $data, $requestTime = null)
    {
        try {
            $processing = $action->getProcessing();

            $processing->setSettings('result_data', $data);
            $processing->setData('is_completed', 1);

            if ($requestTime !== null) {
                $processingParams                 = $processing->getParams();
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
