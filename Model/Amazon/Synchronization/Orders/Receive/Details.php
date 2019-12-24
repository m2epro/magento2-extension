<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Orders\Receive;

/**
 * Class \Ess\M2ePro\Model\Amazon\Synchronization\Orders\Receive\Details
 */
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
        $from = new \DateTime('now', new \DateTimeZone('UTC'));
        $from->modify('- 5 days');

        $orderCollection = $this->amazonFactory->getObject('Order')->getCollection();
        $orderCollection->addFieldToFilter('account_id', $account->getId());
        $orderCollection->addFieldToFilter('is_afn_channel', 1);
        $orderCollection->addFieldToFilter('status', ['neq' => \Ess\M2ePro\Model\Amazon\Order::STATUS_PENDING]);
        $orderCollection->addFieldToFilter('create_date', ['gt' => $from->format('Y-m-d H:i:s')]);
        $orderCollection->addFieldToFilter(
            ['additional_data', 'additional_data'],
            [
                ['additional_data', 'null' => true],
                ['additional_data', 'nlike' => '%fulfillment_details%']
            ]
        );

        $amazonOrdersIds = $orderCollection->getColumnValues('amazon_order_id');
        if (empty($amazonOrdersIds)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcherObject */
        $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
        $connectorObj = $dispatcherObject->getCustomConnector(
            'Amazon_Synchronization_Orders_Receive_Details_Requester',
            ['items' => $amazonOrdersIds],
            $account
        );
        $dispatcherObject->process($connectorObj);
    }

    //########################################
}
