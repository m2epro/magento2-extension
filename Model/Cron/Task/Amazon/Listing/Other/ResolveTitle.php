<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Other;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Other\ResolveTitle
 */
class ResolveTitle extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/listing/other/resolve_title';

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

        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_OTHER_LISTINGS);

        return $synchronizationLog;
    }

    //########################################

    protected function performActions()
    {
        /** @var $accountsCollection \Ess\M2ePro\Model\ResourceModel\Account\Collection */
        $accountsCollection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Amazon::NICK, 'Account')
            ->getCollection();
        $accountsCollection->addFieldToFilter('other_listings_synchronization', 1);

        $accounts = $accountsCollection->getItems();

        if (empty($accounts)) {
            return;
        }

        foreach ($accounts as $account) {

            /** @var $account \Ess\M2ePro\Model\Account **/

            $this->getOperationHistory()->addTimePoint(
                __METHOD__.'process'.$account->getId(),
                'Get and process Titles for Account '.$account->getTitle()
            );

            try {
                $this->updateTitlesByAsins($account);
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module\Translation')->__(
                    'The "Update Titles" Action for Amazon Account "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            $this->getOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());
        }
    }

    //########################################

    protected function updateTitlesByAsins(\Ess\M2ePro\Model\Account $account)
    {
        for ($i = 0; $i <= 5; $i++) {

            /** @var $listingOtherCollection \Ess\M2ePro\Model\ResourceModel\Listing\Other */
            $listingOtherCollection = $this->parentFactory->getObject(
                \Ess\M2ePro\Helper\Component\Amazon::NICK,
                'Listing\Other'
            )->getCollection();
            $listingOtherCollection->addFieldToFilter('main_table.account_id', (int)$account->getId());
            $listingOtherCollection->getSelect()->where('`second_table`.`title` IS NULL');
            $listingOtherCollection->getSelect()->order('main_table.id ASC');
            $listingOtherCollection->getSelect()->limit(5);

            if (!$listingOtherCollection->getSize()) {
                return;
            }

            $neededItems = [];
            /** @var \Ess\M2ePro\Model\Listing\Other $tempItem */
            foreach ($listingOtherCollection->getItems() as $tempItem) {
                $neededItems[] = $tempItem->getChildObject()->getData('general_id');
            }

            $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'product',
                'search',
                'byIdentifiers',
                [
                    'items'         => $neededItems,
                    'id_type'       => 'ASIN',
                    'only_realtime' => 1
                ],
                null,
                $account->getId()
            );

            $dispatcherObject->process($connectorObj);
            $responseData = $connectorObj->getResponseData();

            if (!empty($responseData['unavailable']) && $responseData['unavailable'] == true) {
                return;
            }

            $this->updateReceivedTitles($responseData, $account);
            $this->updateNotReceivedTitles($neededItems, $responseData);
        }
    }

    // ---------------------------------------

    protected function updateReceivedTitles(array $responseData, \Ess\M2ePro\Model\Account $account)
    {
        if (!isset($responseData['items']) || !is_array($responseData['items'])) {
            return;
        }

        $connection = $this->resource->getConnection();

        $aloTable = $this->activeRecordFactory->getObject('Amazon_Listing_Other')->getResource()->getMainTable();

        /** @var $mappingModel \Ess\M2ePro\Model\Amazon\Listing\Other\Mapping */
        $mappingModel = $this->modelFactory->getObject('Amazon_Listing_Other_Mapping');

        $receivedItems = [];
        foreach ($responseData['items'] as $generalId => $item) {
            if ($item == false) {
                continue;
            }

            $item = array_shift($item);
            $title = $item['title'];

            if (isset($receivedItems[$generalId]) || empty($title)) {
                continue;
            }

            $receivedItems[$generalId] = $title;

            $listingsOthersWithEmptyTitles = [];
            if ($account->getChildObject()->isOtherListingsMappingEnabled()) {

                /** @var $listingOtherCollection \Ess\M2ePro\Model\ResourceModel\Listing\Other */
                $listingOtherCollection = $this->parentFactory->getObject(
                    \Ess\M2ePro\Helper\Component\Amazon::NICK,
                    'Listing\Other'
                )->getCollection()
                    ->addFieldToFilter('main_table.account_id', (int)$account->getId())
                    ->addFieldToFilter('second_table.general_id', (int)$generalId)
                    ->addFieldToFilter('second_table.title', ['null' => true]);

                $listingsOthersWithEmptyTitles = $listingOtherCollection->getItems();
            }

            $connection->update(
                $aloTable,
                ['title' => (string)$title],
                ['general_id = ?' => (string)$generalId]
            );

            if (!empty($listingsOthersWithEmptyTitles)) {
                foreach ($listingsOthersWithEmptyTitles as $listingOtherModel) {
                    $listingOtherModel->setData('title', (string)$title);
                    $listingOtherModel->getChildObject()->setData('title', (string)$title);

                    $mappingModel->initialize($account);
                    $mappingModel->autoMapOtherListingProduct($listingOtherModel);
                }
            }
        }
    }

    protected function updateNotReceivedTitles($neededItems, $responseData)
    {

        $connection = $this->resource->getConnection();

        $aloTable = $this->activeRecordFactory->getObject('Amazon_Listing_Other')->getResource()->getMainTable();

        foreach ($neededItems as $generalId) {
            if (isset($responseData['items'][$generalId]) &&
                !empty($responseData['items'][$generalId][0]['title'])) {
                continue;
            }

            $connection->update(
                $aloTable,
                ['title' => \Ess\M2ePro\Model\Amazon\Listing\Other::EMPTY_TITLE_PLACEHOLDER],
                ['general_id = ?' => (string)$generalId]
            );
        }
    }

    //########################################
}
