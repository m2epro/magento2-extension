<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\Channel\SynchronizeData;

use Ess\M2ePro\Helper\Component\Walmart;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\Channel\SynchronizeData\Blocked
 */
class Blocked extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'walmart/listing/product/channel/synchronize_data/blocked';

    /**
     * @var int (in seconds)
     */
    protected $interval = 86400;

    //####################################

    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    protected function getSynchronizationLog()
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(Walmart::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_LISTINGS_PRODUCTS);

        return $synchronizationLog;
    }

    //########################################

    protected function performActions()
    {
        $accounts = $this->parentFactory->getObject(Walmart::NICK, 'Account')->getCollection()->getItems();

        if (empty($accounts)) {
            return;
        }

        foreach ($accounts as $account) {
            /** @var $account \Ess\M2ePro\Model\Account **/

            $this->getOperationHistory()->addText('Starting Account "'.$account->getTitle().'"');

            $this->getOperationHistory()->addTimePoint(
                __METHOD__.'process'.$account->getId(),
                'Process Account '.$account->getTitle()
            );

            try {
                $this->processAccount($account);
            } catch (\Exception $exception) {
                $message = 'The "Update Blocked Listings Products" Action for Walmart Account "%account%"';
                $message .= ' was completed with error.';
                $message = $this->getHelper('Module_Translation')->__($message, $account->getTitle());

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            $this->getOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());
        }
    }

    //########################################

    protected function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel */
        $collection = $this->activeRecordFactory->getObject('Listing')->getCollection();
        $collection->addFieldToFilter('component_mode', Walmart::NICK);
        $collection->addFieldToFilter('account_id', (int)$account->getId());

        if (!$collection->getSize()) {
            return;
        }

        $dispatcher = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');
        $connector = $dispatcher->getVirtualConnector('inventory', 'get', 'wpidsItems', [], 'data', $account);
        $dispatcher->process($connector);

        if (!$this->isNeedProcessResponse($connector->getResponse())) {
            $this->processResponseMessages($connector->getResponseMessages());
            return;
        }

        $wpids = $connector->getResponseData();

        $connection = $this->resource->getConnection();

        /** @var $stmtTemp \Zend_Db_Statement_Pdo */
        $stmtTemp = $connection->query($this->getPdoStatementExistingListings($account));

        $tempLog = $this->activeRecordFactory->getObject('Listing\Log');
        $tempLog->setComponentMode(Walmart::NICK);

        $logsActionId = $this->activeRecordFactory->getObject('Listing\Log')
            ->getResource()->getNextActionId();

        $notReceivedIds = [];
        while ($existingItem = $stmtTemp->fetch()) {
            if (in_array($existingItem['wpid'], $wpids)) {
                continue;
            }

            $notReceivedItem = $existingItem;

            if (!in_array((int)$notReceivedItem['id'], $notReceivedIds)) {
                $statusChangedFrom = $this->getHelper('Component_Walmart')
                    ->getHumanTitleByListingProductStatus($notReceivedItem['status']);
                $statusChangedTo = $this->getHelper('Component_Walmart')
                    ->getHumanTitleByListingProductStatus(\Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED);

                $tempLogMessage = $this->getHelper('Module_Translation')->__(
                    'Item Status was successfully changed from "%from%" to "%to%" .',
                    $statusChangedFrom,
                    $statusChangedTo
                );

                $tempLog->addProductMessage(
                    $notReceivedItem['listing_id'],
                    $notReceivedItem['product_id'],
                    $notReceivedItem['id'],
                    \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
                    $logsActionId,
                    \Ess\M2ePro\Model\Listing\Log::ACTION_CHANNEL_CHANGE,
                    $tempLogMessage,
                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS,
                    \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
                );

                if (!empty($notReceivedItem['is_variation_product']) &&
                    !empty($notReceivedItem['variation_parent_id'])
                ) {
                    $parentIdsForProcessing[] = $notReceivedItem['variation_parent_id'];
                }
            }

            $notReceivedIds[] = (int)$notReceivedItem['id'];
        }

        $notReceivedIds = array_unique($notReceivedIds);

        $mainBind = [
            'status'         => \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED,
            'status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT,
        ];

        $childBind = [
            'is_missed_on_channel' => 1,
        ];

        $listingProductMainTable = $this->activeRecordFactory->getObject('Listing\Product')
            ->getResource()->getMainTable();
        $listingProductChildTable = $this->activeRecordFactory->getObject('Walmart_Listing_Product')
            ->getResource()->getMainTable();

        $chunckedIds = array_chunk($notReceivedIds, 1000);
        foreach ($chunckedIds as $partIds) {
            $where = '`id` IN ('.implode(',', $partIds).')';
            $connection->update($listingProductMainTable, $mainBind, $where);

            $where = '`listing_product_id` IN ('.implode(',', $partIds).')';
            $connection->update($listingProductChildTable, $childBind, $where);
        }

        if (!empty($parentIdsForProcessing)) {
            $this->processParentProcessors($parentIdsForProcessing);
        }
    }

    protected function getPdoStatementExistingListings(\Ess\M2ePro\Model\Account $account)
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel */
        $collection = $this->parentFactory->getObject(Walmart::NICK, 'Listing\Product')->getCollection();
        $collection->getSelect()->join(
            ['l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
            'main_table.listing_id = l.id',
            []
        );

        $collection->addFieldToFilter('l.account_id', (int)$account->getId());
        $collection->addFieldToFilter(
            'status',
            [
                'nin' => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED,
                \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED
                ]
            ]
        );
        $collection->addFieldToFilter('is_variation_parent', ['neq' => 1]);
        $collection->addFieldToFilter('is_missed_on_channel', ['neq' => 1]);

        /**
         * Wait for 24 hours before the newly listed item can be marked as inactive blocked
         */
        $borderDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $borderDate->modify('- 24 hours');
        $collection->addFieldToFilter(
            new \Zend_Db_Expr('list_date IS NULL OR list_date'),
            ['lt' => $borderDate->format('Y-m-d H:i:s')]
        );

        $collection->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(
                [
                    'main_table.id',
                    'main_table.status',
                    'main_table.listing_id',
                    'main_table.product_id',
                    'second_table.wpid',
                    'second_table.is_variation_product',
                    'second_table.variation_parent_id'
                ]
            );

        return $collection->getSelect()->__toString();
    }

    //########################################

    protected function processParentProcessors(array $parentIds)
    {
        if (empty($parentIds)) {
            return;
        }

        /** @var $collection \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel */
        $collection = $this->parentFactory->getObject(Walmart::NICK, 'Listing\Product')
            ->getCollection();
        $collection->addFieldToFilter('id', ['in' => array_unique($parentIds)]);

        $parentListingsProducts = $collection->getItems();
        if (empty($parentListingsProducts)) {
            return;
        }

        $massProcessor = $this->modelFactory->getObject(
            'Walmart_Listing_Product_Variation_Manager_Type_Relation_ParentRelation_Processor_Mass'
        );
        $massProcessor->setListingsProducts($parentListingsProducts);
        $massProcessor->setForceExecuting(false);

        $massProcessor->execute();
    }

    //########################################

    protected function processResponseMessages(array $messages = [])
    {
        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messagesSet */
        $messagesSet = $this->modelFactory->getObject('Connector_Connection_Response_Message_Set');
        $messagesSet->init($messages);

        foreach ($messagesSet->getEntities() as $message) {
            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            $logType = $message->isError() ? \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                : \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;

            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module_Translation')->__($message->getText()),
                $logType,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );
        }
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Connector\Connection\Response $response
     * @return bool
     */
    protected function isNeedProcessResponse($response)
    {
        return !$response->getMessages()->hasErrorEntities();
    }

    //########################################
}
