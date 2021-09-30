<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order;

use Ess\M2ePro\Model\Amazon\Account as AmazonAccount;
use Ess\M2ePro\Model\Amazon\Order as AmazonOrder;
use Ess\M2ePro\Model\Amazon\Order\Invoice as AmazonOrderInvoice;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Order\SendInvoice
 */
class SendInvoice extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/order/send_invoice';

    /** ~4-10 seconds on call, ~5-10 invoices per minute, 50 requests in 10 minutes */
    const LIMIT_ORDER_CHANGES = 50;

    /** @var int $interval (in seconds) */
    protected $interval = 600;

    protected $maxOrderChangesPerTask = 0;

    protected $universalFactory;
    protected $invocePdfFactory;
    protected $creditNotePdfFactory;
    protected $pdfInvoice;
    protected $pdfCreditmemo;

    //####################################

    public function __construct(
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Ess\M2ePro\Model\Amazon\Order\Invoice\Pdf\InvoiceFactory $invocePdfFactory,
        \Ess\M2ePro\Model\Amazon\Order\Invoice\Pdf\CreditNoteFactory $creditNotePdfFactory,
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
        $this->universalFactory = $universalFactory;
        $this->invocePdfFactory = $invocePdfFactory;
        $this->creditNotePdfFactory = $creditNotePdfFactory;
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

            if ($this->maxOrderChangesPerTask === self::LIMIT_ORDER_CHANGES) {
                break;
            }

            $this->getOperationHistory()->addText('Starting account "' . $account->getTitle() . '"');

            $this->getOperationHistory()->addTimePoint(
                __METHOD__ . 'process' . $account->getId(),
                'Process account ' . $account->getTitle()
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

            $this->getOperationHistory()->saveTimePoint(__METHOD__ . 'process' . $account->getId());
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
        $accountsCollection->getSelect()->where(
            'auto_invoicing = ' . AmazonAccount::AUTO_INVOICING_UPLOAD_MAGENTO_INVOICES .
            ' OR (' .
                'auto_invoicing = ' . AmazonAccount::AUTO_INVOICING_VAT_CALCULATION_SERVICE .
                ' AND ' .
                'invoice_generation = ' . AmazonAccount::INVOICE_GENERATION_BY_EXTENSION .
            ')'
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

            if ($changeParams['invoice_source'] == AmazonOrder::INVOICE_SOURCE_MAGENTO) {
                if (($changeParams['document_type'] == AmazonOrderInvoice::DOCUMENT_TYPE_INVOICE &&
                        !$order->getChildObject()->canSendMagentoInvoice()) ||
                    ($changeParams['document_type'] == AmazonOrderInvoice::DOCUMENT_TYPE_CREDIT_NOTE &&
                        !$order->getChildObject()->canSendMagentoCreditmemo())
                ) {
                    $failedChangesIds[] = $change->getId();
                    continue;
                }

                $this->processMagentoDocument($account, $order, $change);

                if ($changesCount > 1) {
                    $this->trotlleProcess();
                }
                continue;
            }

            if ($changeParams['invoice_source'] == AmazonOrder::INVOICE_SOURCE_EXTENSION) {
                if (!$order->getChildObject()->canSendInvoiceFromReport()) {
                    $failedChangesIds[] = $change->getId();
                    continue;
                }

                $this->processExtensionDocument($order, $change);
            }
        }

        if (!empty($failedChangesIds)) {
            $this->activeRecordFactory->getObject('Order\Change')->getResource()->deleteByIds($failedChangesIds);
        }
    }

    protected function processMagentoDocument($account, $order, $change)
    {
        $changeParams = $change->getParams();
        $documentData = $this->getMagentoDocumentData($order, $changeParams['document_type']);

        $requestData = [
            'change_id'       => $change->getId(),
            'order_id'        => $change->getOrderId(),
            'amazon_order_id' => $order->getChildObject()->getAmazonOrderId(),
            'document_number' => $documentData['document_number'],
            'document_type'   => $changeParams['document_type'],
            'document_pdf'    => $documentData['document_pdf']
        ];

        /** @var $dispatcherObject \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
        $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
        /** @var \Ess\M2ePro\Model\Cron\Task\Amazon\Order\SendInvoice\Requester $connectorObj */
        $connectorObj = $dispatcherObject->getCustomConnector(
            'Cron_Task_Amazon_Order_SendInvoice_Requester',
            ['order' => $requestData],
            $account
        );

        $dispatcherObject->process($connectorObj);
    }

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param $type
     * @return array
     * @throws \Zend_Pdf_Exception
     */
    protected function getMagentoDocumentData($order, $type)
    {
        switch ($type) {
            case AmazonOrderInvoice::DOCUMENT_TYPE_INVOICE:
                /** @var \Magento\Sales\Model\ResourceModel\Order\Invoice\Collection $invoices */
                $invoices = $order->getMagentoOrder()->getInvoiceCollection();
                /** @var \Magento\Sales\Model\Order\Invoice $invoice */
                $invoice = $invoices->getLastItem();

                $pdf = $this->pdfInvoice->getPdf([$invoice]);

                $documentNumber = $invoice->getIncrementId();
                $documentPdf = $pdf->render();
                break;

            case AmazonOrderInvoice::DOCUMENT_TYPE_CREDIT_NOTE:
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
            'document_pdf'    => $documentPdf
        ];
    }

    //########################################

    protected function processExtensionDocument($order, $change)
    {
        $reportData = $order->getChildObject()->getSettings('invoice_data_report');

        $itemsByShippingId = $this->groupItemsByField($reportData['items'], 'shipping-id');

        foreach ($itemsByShippingId as $shippingData) {
            $itemsByInvoiceStatus = $this->groupItemsByField($shippingData, 'invoice-status');

            if (!empty($itemsByInvoiceStatus['InvoicePending'])) {
                $this->processExtensionDocumentInvoice($itemsByInvoiceStatus['InvoicePending'], $order, $change);
            }

            if (!empty($itemsByInvoiceStatus['CreditNotePending'])) {
                $this->processExtensionDocumentCreditNote($itemsByInvoiceStatus['CreditNotePending'], $order, $change);
            }
        }

        $order->setData('invoice_data_report', null);
        $order->save();
    }

    protected function processExtensionDocumentInvoice($items, $order, $change)
    {
        $invocieData = $order->getChildObject()->getSettings('invoice_data_report');
        $invocieData['shipping-id'] = $items[0]['shipping-id'];
        $invocieData['transaction-id'] = $items[0]['transaction-id'];
        $invocieData['items'] = $items;

        /** @var \Ess\M2ePro\Model\Amazon\Order\Invoice $lastInvoice */
        $lastInvoice = $this->activeRecordFactory->getObject('Amazon_Order_Invoice')->getCollection()
            ->addFieldToFilter('document_type', AmazonOrderInvoice::DOCUMENT_TYPE_INVOICE)
            ->setOrder('create_date', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
            ->getFirstItem();

        $lastInvoiceNumber = $lastInvoice->getDocumentNumber();

        /** @var \Magento\Eav\Model\Entity\Increment\NumericValue $incrementModel */
        $incrementModel = $this->universalFactory->create(\Magento\Eav\Model\Entity\Increment\NumericValue::class);
        $incrementModel->setPrefix('IN-')
            ->setPadLength(12)
            ->setLastId($lastInvoiceNumber);

        /** @var \Ess\M2ePro\Model\Amazon\Order\Invoice $invoice */
        $invoice = $this->activeRecordFactory->getObject('Amazon_Order_Invoice');
        $invoice->addData([
            'order_id'        => $order->getId(),
            'document_type'   => AmazonOrderInvoice::DOCUMENT_TYPE_INVOICE,
            'document_number' => $incrementModel->getNextId()
        ]);
        $invoice->setSettings('document_data', $invocieData);
        $invoice->save();

        /** @var \Ess\M2ePro\Model\Amazon\Order\Invoice\Pdf\Invoice $orderPdfInvoice */
        $orderPdfInvoice = $this->invocePdfFactory->create();
        $orderPdfInvoice->setOrder($order);
        $orderPdfInvoice->setInvocie($invoice);
        $pdf = $orderPdfInvoice->getPdf();

        $documentPdf = $pdf->render();

        $requestData = [
            'change_id'                 => $change->getId(),
            'order_id'                  => $change->getOrderId(),
            'amazon_order_id'           => $invoice->getSetting('document_data', 'order-id'),
            'document_shipping_id'      => $invoice->getSetting('document_data', 'shipping-id'),
            'document_transaction_id'   => $invoice->getSetting('document_data', 'transaction-id'),
            'document_total_amount'     => $orderPdfInvoice->getDocumentTotal(),
            'document_total_vat_amount' => $orderPdfInvoice->getDocumentVatTotal(),
            'document_type'             => $invoice->getDocumentType(),
            'document_number'           => $invoice->getDocumentNumber(),
            'document_pdf'              => $documentPdf
        ];

        /** @var $dispatcherObject \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
        $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
        /** @var \Ess\M2ePro\Model\Cron\Task\Amazon\Order\SendInvoice\Requester $connectorObj */
        $connectorObj = $dispatcherObject->getCustomConnector(
            'Cron_Task_Amazon_Order_SendInvoice_Requester',
            ['order' => $requestData],
            $order->getAccount()
        );

        $dispatcherObject->process($connectorObj);
        $this->trotlleProcess();
    }

    protected function processExtensionDocumentCreditNote($items, $order, $change)
    {
        $itemsByTransactionId = $this->groupItemsByField($items, 'transaction-id');

        foreach ($itemsByTransactionId as $items) {
            $invocieData = $order->getChildObject()->getSettings('invoice_data_report');
            $invocieData['shipping-id'] = $items[0]['shipping-id'];
            $invocieData['transaction-id'] = $items[0]['transaction-id'];
            $invocieData['items'] = $items;

            /** @var \Ess\M2ePro\Model\Amazon\Order\Invoice $lastInvoice */
            $lastInvoice = $this->activeRecordFactory->getObject('Amazon_Order_Invoice')->getCollection()
                ->addFieldToFilter('document_type', AmazonOrderInvoice::DOCUMENT_TYPE_CREDIT_NOTE)
                ->setOrder('create_date', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
                ->getFirstItem();

            $lastInvoiceNumber = $lastInvoice->getDocumentNumber();

            /** @var \Magento\Eav\Model\Entity\Increment\NumericValue $incrementModel */
            $incrementModel = $this->universalFactory->create(\Magento\Eav\Model\Entity\Increment\NumericValue::class);
            $incrementModel->setPrefix('CN-')
                ->setPadLength(12)
                ->setLastId($lastInvoiceNumber);

            /** @var \Ess\M2ePro\Model\Amazon\Order\Invoice $invoice */
            $invoice = $this->activeRecordFactory->getObject('Amazon_Order_Invoice');
            $invoice->addData([
                'order_id'        => $order->getId(),
                'document_type'   => AmazonOrderInvoice::DOCUMENT_TYPE_CREDIT_NOTE,
                'document_number' => $incrementModel->getNextId()
            ]);
            $invoice->setSettings('document_data', $invocieData);
            $invoice->save();

            /** @var \Ess\M2ePro\Model\Amazon\Order\Invoice\Pdf\CreditNote $orderPdfCreditNote */
            $orderPdfCreditNote = $this->creditNotePdfFactory->create();
            $orderPdfCreditNote->setOrder($order);
            $orderPdfCreditNote->setInvocie($invoice);
            $pdf = $orderPdfCreditNote->getPdf();

            $documentPdf = $pdf->render();

            $requestData = [
                'change_id'                 => $change->getId(),
                'order_id'                  => $change->getOrderId(),
                'amazon_order_id'           => $invoice->getSetting('document_data', 'order-id'),
                'document_shipping_id'      => $invoice->getSetting('document_data', 'shipping-id'),
                'document_transaction_id'   => $invoice->getSetting('document_data', 'transaction-id'),
                'document_total_amount'     => $orderPdfCreditNote->getDocumentTotal(),
                'document_total_vat_amount' => $orderPdfCreditNote->getDocumentVatTotal(),
                'document_type'             => $invoice->getDocumentType(),
                'document_number'           => $invoice->getDocumentNumber(),
                'document_pdf'              => $documentPdf
            ];

            /** @var $dispatcherObject \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
            $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
            /** @var \Ess\M2ePro\Model\Cron\Task\Amazon\Order\SendInvoice\Requester $connectorObj */
            $connectorObj = $dispatcherObject->getCustomConnector(
                'Cron_Task_Amazon_Order_SendInvoice_Requester',
                ['order' => $requestData],
                $order->getAccount()
            );

            $dispatcherObject->process($connectorObj);
            $this->trotlleProcess();
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
        $changesCollection->addProcessingAttemptDateFilter();
        $changesCollection->addFieldToFilter('component', \Ess\M2ePro\Helper\Component\Amazon::NICK);
        $changesCollection->addFieldToFilter('action', \Ess\M2ePro\Model\Order\Change::ACTION_SEND_INVOICE);
        $changesCollection->getSelect()->joinLeft(
            ['pl' => $this->activeRecordFactory->getObject('Processing_Lock')->getResource()->getMainTable()],
            'pl.object_id = main_table.order_id AND pl.model_name = \'Order\'',
            []
        );
        $changesCollection->addFieldToFilter('pl.id', ['null' => true]);
        $changesCollection->getSelect()->limit(self::LIMIT_ORDER_CHANGES);
        $changesCollection->getSelect()->group(['order_id']);

        $this->maxOrderChangesPerTask += $changesCollection->count();

        return $changesCollection->getItems();
    }

    // ---------------------------------------

    protected function deleteNotActualChanges()
    {
        $this->activeRecordFactory->getObject('Order_Change')->getResource()
            ->deleteByProcessingAttemptCount(
                \Ess\M2ePro\Model\Order\Change::MAX_ALLOWED_PROCESSING_ATTEMPTS,
                \Ess\M2ePro\Helper\Component\Amazon::NICK
            );
    }

    //########################################

    protected function groupItemsByField($data, $field)
    {
        $groupedData = [];
        foreach ($data as $row) {
            $groupedData[$row[$field]][] = $row;
        }
        return $groupedData;
    }

    //########################################

    protected function trotlleProcess()
    {
        /**
         * Amazon trolling 1 request per 3 sec.
         */
        // @codingStandardsIgnoreLine
        sleep(3);
    }

    //########################################
}
