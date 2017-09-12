<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\ListingsProducts\Update\Blocked;

class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\Blocked\ItemsResponser
{
    protected $resourceConnection;

    protected $activeRecordFactory;

    protected $logsActionId = NULL;
    protected $synchronizationLog = NULL;

    // ########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = array()
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($amazonFactory, $response, $helperFactory, $modelFactory, $params);
    }

    // ########################################

    protected function processResponseMessages()
    {
        parent::processResponseMessages();

        foreach ($this->getResponse()->getMessages()->getEntities() as $message) {

            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            $logType = $message->isError() ? \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                : \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;

            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module\Translation')->__($message->getText()),
                $logType,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );
        }
    }

    protected function isNeedProcessResponse()
    {
        if (!parent::isNeedProcessResponse()) {
            return false;
        }

        if ($this->getResponse()->getMessages()->hasErrorEntities()) {
            return false;
        }

        return true;
    }

    // ########################################

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        $this->getSynchronizationLog()->addMessage(
            $this->getHelper('Module\Translation')->__($messageText),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
        );
    }

    // ########################################

    protected function processResponseData()
    {
        try {

            $this->updateBlockedListingProducts();

        } catch (\Exception $exception) {

            $this->getHelper('Module\Exception')->process($exception);

            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module\Translation')->__($exception->getMessage()),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );
        }
    }

    // ########################################

    protected function updateBlockedListingProducts()
    {
        $responseData = $this->getPreparedResponseData();

        if (empty($responseData['data'])) {
            return false;
        }

        $stmtTemp = $this->resourceConnection->getConnection()->query($this->getPdoStatementExistingListings());

        $tempLog = $this->activeRecordFactory->getObject('Listing\Log');
        $tempLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);

        $notReceivedIds = array();
        while ($existingItem = $stmtTemp->fetch()) {

            if (in_array($existingItem['sku'], $responseData['data'])) {
                continue;
            }

            $notReceivedItem = $existingItem;

            $additionalData = $this->getHelper('Data')->jsonDecode($notReceivedItem['additional_data']);
            if (is_array($additionalData) && !empty($additionalData['list_date']) &&
                $this->isProductInfoOutdated($additionalData['list_date'])
            ) {
                continue;
            }

            if (!in_array((int)$notReceivedItem['id'],$notReceivedIds)) {
                $statusChangedFrom = $this->getHelper('Component\Amazon')
                    ->getHumanTitleByListingProductStatus($notReceivedItem['status']);
                $statusChangedTo = $this->getHelper('Component\Amazon')
                    ->getHumanTitleByListingProductStatus(\Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED);

                // M2ePro\TRANSLATIONS
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
                    $this->getLogsActionId(),
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

        if (empty($notReceivedIds)) {
            $this->updateLastListingProductsSynchronization();
        }

        $bind = array(
            'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED,
            'status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT
        );

        $listingProductMainTable = $this->activeRecordFactory->getObject('Listing\Product')
            ->getResource()
            ->getMainTable();

        $chunckedIds = array_chunk($notReceivedIds,1000);
        foreach ($chunckedIds as $partIds) {
            $where = '`id` IN ('.implode(',',$partIds).')';
            $this->resourceConnection->getConnection()->update($listingProductMainTable,$bind,$where);
        }

        if (!empty($parentIdsForProcessing)) {
            $this->processParentProcessors($parentIdsForProcessing);
        }
    }

    protected function getPdoStatementExistingListings()
    {
        $listingTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();

        $collection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()->join(array('l' => $listingTable), 'main_table.listing_id = l.id', array());
        $collection->getSelect()->where('l.account_id = ?',(int)$this->getAccount()->getId());
        $collection->getSelect()->where('second_table.is_variation_parent != ?',1);
        $collection->getSelect()->where('`main_table`.`status` != ?',
            (int)\Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED);
        $collection->getSelect()->where('`main_table`.`status` != ?',
            (int)\Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED);

        $tempColumns = array('main_table.id','main_table.status','main_table.listing_id',
            'main_table.product_id','main_table.additional_data',
            'second_table.sku', 'second_table.is_variation_product','second_table.variation_parent_id');
        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns($tempColumns);

        return $collection->getSelect()->__toString();
    }

    // ########################################

    protected function processParentProcessors(array $parentIds)
    {
        if (empty($parentIds)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $parentListingProductCollection */
        $parentListingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $parentListingProductCollection->addFieldToFilter('id', array('in' => array_unique($parentIds)));

        $parentListingsProducts = $parentListingProductCollection->getItems();
        if (empty($parentListingsProducts)) {
            return;
        }

        $massProcessor = $this->modelFactory->getObject(
            'Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Mass'
        );
        $massProcessor->setListingsProducts($parentListingsProducts);
        $massProcessor->setForceExecuting(false);

        $massProcessor->execute();
    }

    // ########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    protected function getAccount()
    {
        return $this->getObjectByParam('Account','account_id');
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    protected function getMarketplace()
    {
        return $this->getAccount()->getChildObject()->getMarketplace();
    }

    //-----------------------------------------

    protected function updateLastListingProductsSynchronization()
    {
        $additionalData = $this->getHelper('Data')->jsonDecode($this->getAccount()->getAdditionalData());
        $lastSynchData = array(
            'last_listing_products_synchronization' => $this->getHelper('Data')->getCurrentGmtDate()
        );

        if (!empty($additionalData)) {
            $additionalData = array_merge($additionalData, $lastSynchData);
        } else {
            $additionalData = $lastSynchData;
        }

        $this->getAccount()
             ->setAdditionalData($this->getHelper('Data')->jsonEncode($additionalData))
             ->save();
    }

    //-----------------------------------------

    protected function getLogsActionId()
    {
        if (!is_null($this->logsActionId)) {
            return $this->logsActionId;
        }

        return $this->logsActionId = $this->activeRecordFactory->getObject('Listing\Log')
                                          ->getResource()->getNextActionId();
    }

    protected function getSynchronizationLog()
    {
        if (!is_null($this->synchronizationLog)) {
            return $this->synchronizationLog;
        }

        $this->synchronizationLog = $this->activeRecordFactory->getObject('Synchronization\Log');
        $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $this->synchronizationLog->setSynchronizationTask(
            \Ess\M2ePro\Model\Synchronization\Log::TASK_LISTINGS_PRODUCTS
        );

        return $this->synchronizationLog;
    }

    //-----------------------------------------

    private function isProductInfoOutdated($lastDate)
    {
        $lastDate = new \DateTime($lastDate, new \DateTimeZone('UTC'));
        $requestDate = new \DateTime($this->params['request_date'], new \DateTimeZone('UTC'));

        $lastDate->modify('+1 hour');

        return $lastDate > $requestDate;
    }

    // ########################################
}