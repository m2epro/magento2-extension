<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\General\AccountPickupStore;

class Update extends \Ess\M2ePro\Model\Ebay\Synchronization\General\AbstractModel
{
    const MAX_ITEMS_COUNT = 10000;

    //########################################

    protected function getNick()
    {
        return '/account_pickup_store/update/';
    }

    protected function getTitle()
    {
        return 'Pickup Store Update';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 60;
    }

    protected function getPercentsEnd()
    {
        return 100;
    }

    //########################################

    public function performActions()
    {
        $accounts = $this->getHelper('Component\Ebay\PickupStore')->getEnabledAccounts();

        if (count($accounts) <= 0) {
            return;
        }

        $iteration = 1;
        $percentsForOneStep = $this->getPercentsInterval() / count($accounts);

        foreach ($accounts as $account) {

            /** @var \Ess\M2ePro\Model\Account $account */

            $this->getActualOperationHistory()->addText('Starting Account "'.$account->getTitle().'"');
            // M2ePro\TRANSLATIONS
            // The "Synchronize Data" Action for eBay Account: "%account_title%" is started. Please wait...
            $status = 'The "Synchronize Data" Action for eBay Account: "%account_title%" is started. ';
            $status .= 'Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );

            $this->getActualOperationHistory()->addTimePoint(
                __METHOD__.'process'.$account->getId(),
                'Process Account '.$account->getTitle()
            );

            try {

                $this->processAccount($account);

            } catch (\Exception $exception) {

                $message = $this->getHelper('Module\Translation')->__(
                    'The "Synchronize Data" Action for eBay Account: "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());

            // M2ePro\TRANSLATIONS
            // The "Synchronize Data" Action for eBay Account: "%account_title%" is finished. Please wait...
            $status = 'The "Synchronize Data" Action for eBay Account: "%account_title%" is finished.'.
                ' Please wait...';
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
        $collection = $this->activeRecordFactory->getObject('Ebay\Account\PickupStore\State')->getCollection();
        $collection->getSelect()->where('(is_deleted = 1) OR (target_qty != online_qty)');
        $collection->addFieldToFilter('is_in_processing', 0);

        $pickupStoreTable = $this->activeRecordFactory
            ->getObject('Ebay\Account\PickupStore')
            ->getResource()
            ->getMainTable();

        $collection->getSelect()->joinLeft(
            array('eaps' => $pickupStoreTable),
            'eaps.id = main_table.account_pickup_store_id',
            array('account_id')
        );

        $collection->addFieldToFilter('eaps.account_id', $account->getId());

        $collection->getSelect()->limit(self::MAX_ITEMS_COUNT);

        $pickupStoreStateItems = $collection->getItems();
        if (empty($pickupStoreStateItems)) {
            return;
        }

        $dispatcher = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');

        /** @var \Ess\M2ePro\Model\Ebay\Connector\AccountPickupStore\Synchronize\ProductsRequester $connector */
        $connector = $dispatcher->getConnector(
            'accountPickupStore', 'synchronize', 'productsRequester', array(), NULL, $account->getId()
        );
        $connector->setPickupStoreStateItems($pickupStoreStateItems);
        $dispatcher->process($connector);
    }

    //########################################
}