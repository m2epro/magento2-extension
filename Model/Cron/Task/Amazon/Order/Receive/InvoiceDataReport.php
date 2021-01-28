<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive;

/**
 * Class Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\InvoiceDataReport
 */
class InvoiceDataReport extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/order/receive/invoice_data_report';

    /** @var int $_interval (in seconds) */
    protected $_interval = 3600;

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
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_ORDERS);

        return $synchronizationLog;
    }

    //########################################

    protected function performActions()
    {
        $permittedAccounts = $this->getPermittedAccounts();
        if (empty($permittedAccounts)) {
            return;
        }

        foreach ($permittedAccounts as $account) {
            /** @var \Ess\M2ePro\Model\Account $account */

            $this->getOperationHistory()->addText('Starting account "' . $account->getTitle() . '"');

            $this->getOperationHistory()->addTimePoint(
                __METHOD__ . 'process' . $account->getId(),
                'Process account ' . $account->getTitle()
            );

            try {
                $this->processAccount($account);
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module_Translation')->__(
                    'The "Invoice Data Report" Action for Amazon Account "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            $this->getOperationHistory()->saveTimePoint(__METHOD__ . 'process' . $account->getId());
        }
    }

    //########################################

    protected function getPermittedAccounts()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountsCollection */
        $accountsCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Account'
        )->getCollection();
        $accountsCollection->addFieldToFilter(
            'auto_invoicing',
            \Ess\M2ePro\Model\Amazon\Account::AUTO_INVOICING_VAT_CALCULATION_SERVICE
        );
        $accountsCollection->addFieldToFilter(
            'invoice_generation',
            \Ess\M2ePro\Model\Amazon\Account::INVOICE_GENERATION_BY_EXTENSION
        );
        $accountsCollection->setOrder('update_date', 'desc');

        $accountsByMerchantId = [];
        foreach ($accountsCollection->getItems() as $account) {
            /** @var $account \Ess\M2ePro\Model\Account */

            $merchantId = $account->getChildObject()->getMerchantId();
            if (!isset($accountsByMerchantId[$merchantId])) {
                $accountsByMerchantId[$merchantId] = [];
            }

            $accountsByMerchantId[$merchantId][] = $account;
        }

        $accounts = [];
        foreach ($accountsByMerchantId as $accountItems) {
            $accounts[] = $accountItems[0];
        }

        return $accounts;
    }

    // ---------------------------------------

    protected function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcherObject */
        $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
        $connectorObj = $dispatcherObject->getCustomConnector(
            'Cron_Task_Amazon_Order_Receive_InvoiceDataReport_Requester',
            [],
            $account
        );
        $dispatcherObject->process($connectorObj);
    }

    //########################################
}
