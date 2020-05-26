<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Order\SendInvoice
 */
class SendInvoice extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/order/send_invoice';

    protected $fileFactory;
    protected $pdfInvoice;
    protected $pdfCreditmemo;

    //####################################

    public function __construct(
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Sales\Model\Order\Pdf\Invoice $pdfInvoice,
        \Magento\Sales\Model\Order\Pdf\Creditmemo $pdfCreditmemo,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->fileFactory = $fileFactory;
        $this->pdfInvoice = $pdfInvoice;
        $this->pdfCreditmemo = $pdfCreditmemo;

        parent::__construct(
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );
    }

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
        $this->deleteNotActualChanges();

        $permittedAccounts = $this->getPermittedAccounts();
        if (empty($permittedAccounts)) {
            return;
        }

        foreach ($permittedAccounts as $account) {
            /** @var \Ess\M2ePro\Model\Account $account */

            $this->getOperationHistory()->addText('Starting account "'.$account->getTitle().'"');

            $this->getOperationHistory()->addTimePoint(
                __METHOD__.'process'.$account->getId(),
                'Process account '.$account->getTitle()
            );

            try {
                $this->processAccount($account);
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module\Translation')->__(
                    'The "Send Invoice" Action for Amazon Account "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            $this->getOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());
        }
    }

    //########################################

    protected function getPermittedAccounts()
    {
        /** @var $accountsCollection \Ess\M2ePro\Model\ResourceModel\Account\Collection */
        $accountsCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Account'
        )->getCollection();
        $accountsCollection->addFieldToFilter(
            'auto_invoicing',
            \Ess\M2ePro\Model\Amazon\Account::MAGENTO_ORDERS_AUTO_INVOICING_UPLOAD_MAGENTO_INVOICES
        );
        return $accountsCollection->getItems();
    }

    // ---------------------------------------

    protected function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $relatedChanges = $this->getRelatedChanges($account);
        if (empty($relatedChanges)) {
            return;
        }

        $this->activeRecordFactory->getObject('Order_Change')->getResource()
            ->incrementAttemptCount(array_keys($relatedChanges));

        /** @var $dispatcherObject \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
        $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');

        $failedChangesIds = [];
        $changesCount = count($relatedChanges);

        foreach ($relatedChanges as $change) {
            $changeParams = $change->getParams();

            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->parentFactory->getObjectLoaded(
                \Ess\M2ePro\Helper\Component\Amazon::NICK,
                'Order',
                $change->getOrderId()
            );

            if (!$order->getChildObject()->canSendInvoice()) {
                $failedChangesIds[] = $change->getId();
                continue;
            }

            $documentData = $this->getDocumentData($order, $changeParams['document_type']);

            $connectorData = [
                'change_id' => $change->getId(),
                'order_id'  => $change->getOrderId(),
                'amazon_order_id' => $order->getChildObject()->getAmazonOrderId(),
                'document_number' => $documentData['document_number'],
                'document_pdf' => $documentData['document_pdf'],
                'document_type' => $changeParams['document_type']
            ];

            /** @var \Ess\M2ePro\Model\Cron\Task\Amazon\Order\SendInvoice\Requester $connectorObj */
            $connectorObj = $dispatcherObject->getCustomConnector(
                'Cron_Task_Amazon_Order_SendInvoice_Requester',
                ['order' => $connectorData],
                $account
            );
            $dispatcherObject->process($connectorObj);

            /**
             * Amazon trolling 1 request per 3 sec.
             */
            if ($changesCount > 1) {
                // @codingStandardsIgnoreLine
                sleep(3);
            }
        }

        if (!empty($failedChangesIds)) {
            $this->activeRecordFactory->getObject('Order\Change')->getResource()->deleteByIds($failedChangesIds);
        }
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @return \Ess\M2ePro\Model\Order\Change[]
     */
    protected function getRelatedChanges(\Ess\M2ePro\Model\Account $account)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Change\Collection $changesCollection */
        $changesCollection = $this->activeRecordFactory->getObject('Order_Change')->getCollection();
        $changesCollection->addAccountFilter($account->getId());
        $changesCollection->addProcessingAttemptDateFilter(10);
        $changesCollection->addFieldToFilter('component', \Ess\M2ePro\Helper\Component\Amazon::NICK);
        $changesCollection->addFieldToFilter('action', \Ess\M2ePro\Model\Order\Change::ACTION_SEND_INVOICE);
        $changesCollection->getSelect()->joinLeft(
            ['pl' => $this->activeRecordFactory->getObject('Processing_Lock')->getResource()->getMainTable()],
            'pl.object_id = main_table.order_id AND pl.model_name = \'Order\'',
            []
        );
        $changesCollection->addFieldToFilter('pl.id', ['null' => true]);
        $changesCollection->getSelect()->group(['order_id']);

        return $changesCollection->getItems();
    }

    // ---------------------------------------

    protected function deleteNotActualChanges()
    {
        $this->activeRecordFactory->getObject('Order_Change')->getResource()->deleteByProcessingAttemptCount(
            \Ess\M2ePro\Model\Order\Change::MAX_ALLOWED_PROCESSING_ATTEMPTS,
            \Ess\M2ePro\Helper\Component\Amazon::NICK
        );
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param $type
     * @return array
     * @throws \Zend_Pdf_Exception
     */
    protected function getDocumentData($order, $type)
    {
        switch ($type) {
            case \Ess\M2ePro\Model\Amazon\Order::DOCUMENT_TYPE_INVOICE:
                /** @var \Magento\Sales\Model\ResourceModel\Order\Invoice\Collection $invoices */
                $invoices = $order->getMagentoOrder()->getInvoiceCollection();
                /** @var \Magento\Sales\Model\Order\Invoice $invoice */
                $invoice = $invoices->getLastItem();

                $pdf = $this->pdfInvoice->getPdf([$invoice]);

                $documentNumber = $invoice->getIncrementId();
                $documentPdf = $pdf->render();
                break;

            case \Ess\M2ePro\Model\Amazon\Order::DOCUMENT_TYPE_CREDIT_NOTE:
                /** @var \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection $creditmemos */
                $creditmemos = $order->getMagentoOrder()->getCreditmemosCollection();
                /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
                $creditmemo = $creditmemos->getLastItem();

                $pdf = $this->pdfCreditmemo->getPdf([$creditmemo]);

                $documentNumber = $creditmemo->getIncrementId();
                $documentPdf = $pdf->render();
                break;

            default:
                $documentNumber = '';
                $documentPdf = '';
                break;
        }

        return [
            'document_number' => $documentNumber,
            'document_pdf' => $documentPdf
        ];
    }

    //########################################
}
