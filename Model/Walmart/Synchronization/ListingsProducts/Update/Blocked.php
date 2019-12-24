<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\ListingsProducts\Update;

use Ess\M2ePro\Model\Processing\Runner;

/**
 * Class \Ess\M2ePro\Model\Walmart\Synchronization\ListingsProducts\Update\Blocked
 */
class Blocked extends \Ess\M2ePro\Model\Walmart\Synchronization\ListingsProducts\AbstractModel
{
    const LOCK_ITEM_PREFIX = 'synchronization_walmart_listings_products_update_blocked';

    //########################################

    protected function getNick()
    {
        return '/update/blocked/';
    }

    protected function getTitle()
    {
        return 'Update Blocked Listings Products';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 25;
    }

    protected function getPercentsEnd()
    {
        return 50;
    }

    // ---------------------------------------

    protected function intervalIsEnabled()
    {
        return true;
    }

    //########################################

    protected function performActions()
    {
        $accounts = $this->walmartFactory->getObject('Account')->getCollection()->getItems();

        if (count($accounts) <= 0) {
            return;
        }

        $iteration = 0;
        $percentsForOneStep = $this->getPercentsInterval() / count($accounts);

        foreach ($accounts as $account) {

            /** @var $account \Ess\M2ePro\Model\Account **/

            $this->getActualOperationHistory()->addText('Starting Account "'.$account->getTitle().'"');
            $status = 'The "Update Blocked Listings Products" Action for Walmart Account: ';
            $status .= '"%account_title%" is started. ';
            $status .= 'Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );

            if (!$this->isLockedAccount($account) && !$this->isLockedAccountInterval($account)) {
                $this->getActualOperationHistory()->addTimePoint(
                    __METHOD__.'process'.$account->getId(),
                    'Process Account '.$account->getTitle()
                );

                try {
                    $this->processAccount($account);
                } catch (\Exception $exception) {
                    $message = 'The "Update Blocked Listings Products" Action for Walmart Account "%account%"';
                    $message .= ' was completed with error.';
                    $message = $this->getHelper('Module\Translation')->__($message, $account->getTitle());

                    $this->processTaskAccountException($message, __FILE__, __LINE__);
                    $this->processTaskException($exception);
                }

                $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());
            }

            $status = 'The "Update Blocked Listings Products" Action for Walmart Account: ';
            $status .= '"%account_title%" is finished. ';
            $status .= 'Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );
            $this->getActualLockItem()->setPercents($this->getPercentsStart() + $iteration * $percentsForOneStep);
            $this->getActualLockItem()->activate();

            $iteration++;
        }
    }

    //########################################

    private function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $collection = $this->activeRecordFactory->getObject('Listing')->getCollection();
        $collection->addFieldToFilter('component_mode', \Ess\M2ePro\Helper\Component\Walmart::NICK);
        $collection->addFieldToFilter('account_id', (int)$account->getId());

        if (!$collection->getSize()) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $dispatcher */
        $dispatcher = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');
        $connector = $dispatcher->getVirtualConnector('inventory', 'get', 'wpidsItems', [], 'data', $account);
        $dispatcher->process($connector);

        if (!$this->isNeedProcessResponse($connector->getResponse())) {
            $this->processResponseMessages($connector->getResponseMessages());
            return;
        }

        $wpids = $connector->getResponseData();

        $connRead = $this->resourceConnection->getConnection();
        $stmtTemp = $connRead->query($this->getPdoStatementExistingListings($account));

        $tempLog = $this->activeRecordFactory->getObject('Listing\Log');
        $tempLog->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK);

        $logsActionId = $tempLog->getResource()->getNextActionId();

        $notReceivedIds = [];
        while ($existingItem = $stmtTemp->fetch()) {
            if (in_array($existingItem['wpid'], $wpids)) {
                continue;
            }

            $notReceivedItem = $existingItem;

            if (!in_array((int)$notReceivedItem['id'], $notReceivedIds)) {
                $statusChangedFrom = $this->getHelper('Component\Walmart')
                                         ->getHumanTitleByListingProductStatus($notReceivedItem['status']);
                $statusChangedTo = $this->getHelper('Component\Walmart')
                                       ->getHumanTitleByListingProductStatus(
                                           \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED
                                       );

                // M2ePro_TRANSLATIONS
                // Item Status was successfully changed from "%from%" to "%to%" .
                $tempLogMessage = $this->getHelper('Module\Translation')->__(
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
                    \Ess\M2ePro\Model\Listing\Log::TYPE_SUCCESS,
                    \Ess\M2ePro\Model\Listing\Log::PRIORITY_LOW
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

        if (empty($notReceivedIds)) {
            $this->updateLastListingProductsSynchronization($account);
        }

        $mainBind = [
            'status'         => \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED,
            'status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT,
        ];

        $childBind = [
            'is_missed_on_channel' => 1,
        ];

        $connWrite = $this->resourceConnection->getConnection();

        $listingProductMainTable = $this->activeRecordFactory->getObject('Listing\Product')
                                                             ->getResource()
                                                             ->getMainTable();
        $listingProductChildTable =$this->activeRecordFactory->getObject('Walmart_Listing_Product')
                                                             ->getResource()
                                                             ->getMainTable();

        $chunckedIds = array_chunk($notReceivedIds, 1000);
        foreach ($chunckedIds as $partIds) {
            $where = '`id` IN ('.implode(',', $partIds).')';
            $connWrite->update($listingProductMainTable, $mainBind, $where);

            $where = '`listing_product_id` IN ('.implode(',', $partIds).')';
            $connWrite->update($listingProductChildTable, $childBind, $where);
        }

        if (!empty($parentIdsForProcessing)) {
            $this->processParentProcessors($parentIdsForProcessing);
        }
    }

    protected function getPdoStatementExistingListings(\Ess\M2ePro\Model\Account $account)
    {
        $collection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()->join(
            ['l' => $this->activeRecordFactory->getObject('Listing')
                                                   ->getResource()
                                                   ->getMainTable()],
            'main_table.listing_id = l.id',
            []
        );

        $collection->addFieldToFilter('l.account_id', (int)$account->getId());
        $collection->addFieldToFilter('status', ['nin' => [
            \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED,
            \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED
        ]]);
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
                   ->columns([
                       'main_table.id',
                       'main_table.status',
                       'main_table.listing_id',
                       'main_table.product_id',
                       'second_table.wpid',
                       'second_table.is_variation_product',
                       'second_table.variation_parent_id'
                   ]);

        return $collection->getSelect()->__toString();
    }

    protected function processParentProcessors(array $parentIds)
    {
        if (empty($parentIds)) {
            return;
        }

        $parentListingProductCollection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $parentListingProductCollection->addFieldToFilter('id', ['in' => array_unique($parentIds)]);

        $parentListingsProducts = $parentListingProductCollection->getItems();
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

    protected function updateLastListingProductsSynchronization(\Ess\M2ePro\Model\Account $account)
    {
        $additionalData = $this->getHelper('Data')->jsonDecode($account->getAdditionalData());
        $lastSynchData = [
            'last_listing_products_synchronization' => $this->getHelper('Data')->getCurrentGmtDate()
        ];

        if (!empty($additionalData)) {
            $additionalData = array_merge($additionalData, $lastSynchData);
        } else {
            $additionalData = $lastSynchData;
        }

        $account->setSettings('additional_data', $additionalData)
                ->save();
    }

    //########################################

    private function isLockedAccount(\Ess\M2ePro\Model\Account $account)
    {
        /** @var $lockItem \Ess\M2ePro\Model\Lock\Item\Manager */
        $lockItem = $this->modelFactory->getObject('Lock_Item_Manager');
        $lockItem->setNick(self::LOCK_ITEM_PREFIX.'_'.$account->getId());
        $lockItem->setMaxInactiveTime(Runner::MAX_LIFETIME);

        return $lockItem->isExist();
    }

    private function isLockedAccountInterval(\Ess\M2ePro\Model\Account $account)
    {
        if ($this->getInitiator() == \Ess\M2ePro\Helper\Data::INITIATOR_USER ||
            $this->getInitiator() == \Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER) {
            return false;
        }

        $additionalData = $this->getHelper('Data')->jsonDecode($account->getAdditionalData());
        if (!empty($additionalData['last_listing_products_synchronization'])) {
            return (strtotime($additionalData['last_listing_products_synchronization'])
                   + 86400) > $this->getHelper('Data')->getCurrentGmtDate(true);
        }

        return false;
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

            $this->getLog()->addMessage(
                $this->getHelper('Module\Translation')->__($message->getText()),
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
