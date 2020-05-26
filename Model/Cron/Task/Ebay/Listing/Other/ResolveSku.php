<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Other;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Other\ResolveSku
 */
class ResolveSku extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'ebay/listing/other/resolve_sku';

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

        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_OTHER_LISTINGS);

        return $synchronizationLog;
    }

    //########################################

    protected function performActions()
    {
        /** @var $accountsCollection \Ess\M2ePro\Model\ResourceModel\Account\Collection */
        $accountsCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'Account'
        )->getCollection();
        $accountsCollection->addFieldToFilter('other_listings_synchronization', 1);

        $accounts = $accountsCollection->getItems();

        if (empty($accounts)) {
            return;
        }

        foreach ($accounts as $account) {
            /** @var $account \Ess\M2ePro\Model\Account **/

            $this->getOperationHistory()->addTimePoint(
                __METHOD__.'process'.$account->getId(),
                'Get and process SKUs for Account '.$account->getTitle()
            );

            try {
                $this->updateSkus($account);
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module\Translation')->__(
                    'The "Update SKUs" Action for eBay Account "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            $this->getOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());
        }
    }

    //########################################

    protected function updateSkus(\Ess\M2ePro\Model\Account $account)
    {
        /** @var $listingOtherCollection \Ess\M2ePro\Model\ResourceModel\Listing\Other */

        $listingOtherCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'Listing\Other'
        )->getCollection();
        $listingOtherCollection->addFieldToFilter('main_table.account_id', (int)$account->getId());
        $listingOtherCollection->getSelect()->where('`second_table`.`sku` IS NULL');
        $listingOtherCollection->getSelect()->order('second_table.start_date ASC');
        $listingOtherCollection->getSelect()->limit(200);

        if (!$listingOtherCollection->getSize()) {
            return;
        }

        $firstItem = $listingOtherCollection->getFirstItem();

        $sinceTime = $firstItem->getData('start_date');
        $receivedData = $this->receiveSkusFromEbay($account, $sinceTime);

        if (empty($receivedData['items'])) {
            foreach ($listingOtherCollection->getItems() as $listingOther) {
                $listingOther->getChildObject()->setData('sku', '')->save();
            }

            return;
        }

        $this->updateSkusForReceivedItems($listingOtherCollection, $account, $receivedData['items']);
        $this->updateSkusForNotReceivedItems($listingOtherCollection, $receivedData['to_time']);
    }

    // ---------------------------------------

    protected function updateSkusForReceivedItems(
        $listingOtherCollection,
        \Ess\M2ePro\Model\Account $account,
        array $items
    ) {
        /** @var $mappingModel \Ess\M2ePro\Model\Ebay\Listing\Other\Mapping */
        $mappingModel = $this->modelFactory->getObject('Ebay_Listing_Other_Mapping');

        foreach ($items as $item) {
            foreach ($listingOtherCollection->getItems() as $listingOther) {

                /** @var $listingOther \Ess\M2ePro\Model\Listing\Other */

                if ((float)$listingOther->getData('item_id') != $item['id']) {
                    continue;
                }

                $listingOther->getChildObject()->setData('sku', (string)$item['sku'])->save();

                if ($account->getChildObject()->isOtherListingsMappingEnabled()) {
                    $mappingModel->initialize($account);
                    $mappingModel->autoMapOtherListingProduct($listingOther);
                }

                break;
            }
        }
    }

    // eBay item IDs which were removed can lead to the issue and getting SKU process freezes
    protected function updateSkusForNotReceivedItems($listingOtherCollection, $toTimeReceived)
    {
        foreach ($listingOtherCollection->getItems() as $listingOther) {

            /** @var \Ess\M2ePro\Model\Ebay\Listing\Other $ebayListingOther */
            $ebayListingOther = $listingOther->getChildObject();

            if ($ebayListingOther->getSku() !== null) {
                continue;
            }

            if (strtotime($ebayListingOther->getStartDate()) >= strtotime($toTimeReceived)) {
                continue;
            }

            $ebayListingOther->setData('sku', '')->save();
        }
    }

    //########################################

    protected function receiveSkusFromEbay(\Ess\M2ePro\Model\Account $account, $sinceTime)
    {
        $sinceTime = new \DateTime($sinceTime, new \DateTimeZone('UTC'));
        $sinceTime->modify('-1 minute');
        $sinceTime = $sinceTime->format('Y-m-d H:i:s');

        $inputData = [
            'since_time'    => $sinceTime,
            'only_one_page' => true,
            'realtime'      => true
        ];

        $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector(
            'inventory',
            'get',
            'items',
            $inputData,
            null,
            null,
            $account->getId()
        );

        $dispatcherObj->process($connectorObj);
        $responseData = $connectorObj->getResponseData();

        if (!isset($responseData['items']) || !is_array($responseData['items'])) {
            return [];
        }

        return $responseData;
    }

    //########################################
}
