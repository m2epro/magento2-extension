<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\OtherListings;

class Sku extends AbstractModel
{
    //########################################

    /**
     * @return string
     */
    protected function getNick()
    {
        return '/sku/';
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return 'Sku';
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getPercentsStart()
    {
        return 40;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 50;
    }

    //########################################

    protected function performActions()
    {
        $accountsCollection = $this->ebayFactory->getObject('Account')->getCollection();
        $accountsCollection->addFieldToFilter('other_listings_synchronization',
           \Ess\M2ePro\Model\Ebay\Account::OTHER_LISTINGS_SYNCHRONIZATION_YES);

        $accounts = $accountsCollection->getItems();

        if (count($accounts) <= 0) {
            return;
        }

        $iteration = 0;
        $percentsForOneStep = ($this->getPercentsInterval()/2) / count($accounts);

        foreach ($accounts as $account) {

            /** @var $account \Ess\M2ePro\Model\Account **/

            $this->getActualOperationHistory()->addTimePoint(
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

            $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());

            $offset = $this->getPercentsInterval() / 2 + $iteration * $percentsForOneStep;
            $this->getActualLockItem()->setPercents($offset);
            $this->getActualLockItem()->activate();

            $iteration++;
        }
    }

    //########################################

    private function updateSkus(\Ess\M2ePro\Model\Account $account)
    {
        $listingOtherCollection = $this->ebayFactory->getObject('Listing\Other')->getCollection();
        $listingOtherCollection->addFieldToFilter('main_table.account_id',(int)$account->getId());
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
                $listingOther->getChildObject()->setData('sku','')->save();
            }
            return;
        }

        $this->updateSkusForReceivedItems($listingOtherCollection, $account, $receivedData['items']);
        $this->updateSkusForNotReceivedItems($listingOtherCollection, $receivedData['to_time']);
    }

    // ---------------------------------------

    private function updateSkusForReceivedItems(
        $listingOtherCollection,
        \Ess\M2ePro\Model\Account $account,
        array $items
    ) {
        /** @var $mappingModel \Ess\M2ePro\Model\Ebay\Listing\Other\Mapping */
        $mappingModel = $this->modelFactory->getObject('Ebay\Listing\Other\Mapping');

        foreach ($items as $item) {
            foreach ($listingOtherCollection->getItems() as $listingOther) {

                /** @var $listingOther \Ess\M2ePro\Model\Listing\Other */

                if ((float)$listingOther->getData('item_id') != $item['id']) {
                    continue;
                }

                $listingOther->getChildObject()->setData('sku',(string)$item['sku'])->save();

                if ($account->getChildObject()->isOtherListingsMappingEnabled()) {
                    $mappingModel->initialize($account);
                    $mappingModel->autoMapOtherListingProduct($listingOther);
                }

                break;
            }
        }
    }

    // eBay item IDs which were removed can lead to the issue and getting SKU process freezes
    private function updateSkusForNotReceivedItems($listingOtherCollection, $toTimeReceived)
    {
        foreach ($listingOtherCollection->getItems() as $listingOther) {

            /** @var \Ess\M2ePro\Model\Ebay\Listing\Other $ebayListingOther */
            $ebayListingOther = $listingOther->getChildObject();

            if (!is_null($ebayListingOther->getSku())) {
                continue;
            }

            if (strtotime($ebayListingOther->getStartDate()) >= strtotime($toTimeReceived)) {
                continue;
            }

            $ebayListingOther->setData('sku', '')->save();
        }
    }

    //########################################

    private function receiveSkusFromEbay(\Ess\M2ePro\Model\Account $account, $sinceTime)
    {
        $sinceTime = new \DateTime($sinceTime,new \DateTimeZone('UTC'));
        $sinceTime->modify('-1 minute');
        $sinceTime = $sinceTime->format('Y-m-d H:i:s');

        $inputData = array(
            'since_time'    => $sinceTime,
            'only_one_page' => true
        );

        $dispatcherObj = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector(
            'item','get','all',
            $inputData,NULL,
            NULL,$account->getId()
        );

        $dispatcherObj->process($connectorObj);
        $responseData = $connectorObj->getResponseData();

        if (!isset($responseData['items']) || !is_array($responseData['items'])) {
            return array();
        }

        return $responseData;
    }

    //########################################
}