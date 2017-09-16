<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Orders\Receive;

class Details extends \Ess\M2ePro\Model\Amazon\Synchronization\Orders\AbstractModel
{
    //########################################

    protected function getNick()
    {
        return '/receive_details/';
    }

    protected function getTitle()
    {
        return 'Receive Details';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 0;
    }

    protected function getPercentsEnd()
    {
        return 100;
    }

    // ---------------------------------------

    protected function intervalIsEnabled()
    {
        return true;
    }

    //########################################

    protected function performActions()
    {
        $permittedAccounts = $this->getPermittedAccounts();
        if (empty($permittedAccounts)) {
            return;
        }

        $iteration = 0;
        $percentsForOneStep = $this->getPercentsInterval() / count($permittedAccounts);

        foreach ($permittedAccounts as $account) {

            /** @var \Ess\M2ePro\Model\Account $account */

            // ---------------------------------------
            $this->getActualOperationHistory()->addText('Starting account "'.$account->getTitle().'"');
            // M2ePro_TRANSLATIONS
            // The "Receive Details" action for Amazon account: "%account_title%" is started. Please wait...
            $status = 'The "Receive Details" action for Amazon account: "%account_title%" is started. Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );
            // ---------------------------------------

            // ---------------------------------------
            $this->getActualOperationHistory()->addTimePoint(
                __METHOD__.'process'.$account->getId(),
                'Process account '.$account->getTitle()
            );
            // ---------------------------------------

            try {

                $this->processAccount($account);

            } catch (\Exception $exception) {

                $message = $this->getHelper('Module\Translation')->__(
                    'The "Receive Details" Action for Amazon Account "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            // ---------------------------------------
            $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());
            // ---------------------------------------

            // ---------------------------------------
            // M2ePro_TRANSLATIONS
            // The "Receive Details" action for Amazon account: "%account_title%" is finished. Please wait...
            $status = 'The "Receive Details" action for Amazon account: "%account_title%" is finished. Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );
            $this->getActualLockItem()->setPercents($this->getPercentsStart() + $iteration * $percentsForOneStep);
            $this->getActualLockItem()->activate();
            // ---------------------------------------

            $iteration++;
        }
    }

    //########################################

    private function getPermittedAccounts()
    {
        $accountsCollection = $this->amazonFactory->getObject('Account')->getCollection();
        return $accountsCollection->getItems();
    }

    // ---------------------------------------

    private function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $fromDate = $this->getFromDate($account);

        $orderCollection = $this->amazonFactory->getObject('Order')->getCollection();
        $orderCollection->addFieldToFilter('account_id', $account->getId());
        $orderCollection->addFieldToFilter('is_afn_channel', 1);
        $orderCollection->addFieldToFilter('create_date', array('gt' => $fromDate));

        $amazonOrdersIds = $orderCollection->getColumnValues('amazon_order_id');
        if (empty($amazonOrdersIds)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcherObject */
        $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getCustomConnector(
            'Amazon\Synchronization\Orders\Receive\Details\Requester',
            array('items' => $amazonOrdersIds), $account
        );
        $dispatcherObject->process($connectorObj);

        $this->setFromDate($account);
    }

    //########################################

    private function getFromDate(\Ess\M2ePro\Model\Account $account)
    {
        $accountAdditionalData = $this->getHelper('Data')->jsonDecode($account->getAdditionalData());
        return !empty($accountAdditionalData['amazon_last_receive_fulfillment_details_date']) ?
            $accountAdditionalData['amazon_last_receive_fulfillment_details_date']
            : $this->getHelper('Data')->getCurrentGmtDate();
    }

    private function setFromDate(\Ess\M2ePro\Model\Account $account)
    {
        $fromDate = $this->getHelper('Data')->getCurrentGmtDate();

        $accountAdditionalData = $this->getHelper('Data')->jsonDecode($account->getAdditionalData());
        $accountAdditionalData['amazon_last_receive_fulfillment_details_date'] = $fromDate;
        $account->setSettings('additional_data', $accountAdditionalData)->save();
    }

    //########################################
}